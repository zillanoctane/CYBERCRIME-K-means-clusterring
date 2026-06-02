<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\ClusterAssignment;
use App\Models\ClusteringRun;
use App\Models\CybercrimeRecord;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Orkestrasi proses analisis K-Means:
 *   1. Bangun dataset dari tabel ``cybercrime_records`` sesuai filter & fitur.
 *   2. Panggil ML service untuk Elbow / Clustering.
 *   3. Simpan hasil ke ``clustering_runs`` & ``cluster_assignments``.
 *
 * Service ini sengaja dipisahkan dari controller agar dapat diuji secara unit.
 */
class ClusteringService
{
    /** Daftar fitur yang diperbolehkan (whitelist) untuk mencegah SQL injection via input fitur. */
    public const ALLOWED_NUMERIC = [
        'estimasi_kerugian',
        'jumlah_korban',
        'usia_korban',
        'keparahan_score',
        'durasi_lapor_hari',
    ];

    /**
     * Fitur numerik turunan — dihitung di PHP, BUKAN kolom database.
     * Tidak boleh masuk ke klausa SELECT SQL.
     */
    public const DERIVED_NUMERIC = [
        'keparahan_score',
        'durasi_lapor_hari',
    ];

    /** Kolom asli di tabel cybercrime_records yang aman untuk di-SELECT. */
    private const REAL_COLUMNS = [
        'id',
        'tanggal_kejadian',
        'tanggal_laporan',
        'jenis_kejahatan',
        'sub_jenis',
        'modus_operandi',
        'platform',
        'provinsi',
        'tingkat_keparahan',
        'jenis_kelamin_korban',
        'pendidikan_korban',
        'pekerjaan_korban',
        'sumber_data',
        'estimasi_kerugian',
        'jumlah_korban',
        'usia_korban',
    ];

    public const ALLOWED_CATEGORICAL = [
        'jenis_kejahatan',
        'sub_jenis',
        'modus_operandi',
        'platform',
        'provinsi',
        'tingkat_keparahan',
        'jenis_kelamin_korban',
        'pendidikan_korban',
        'pekerjaan_korban',
        'sumber_data',
    ];

    /** Jumlah baris minimum mutlak yang disyaratkan ML service. */
    public const MIN_ROWS = 10;

    public function __construct(private readonly MLServiceClient $ml) {}

    /**
     * Baris minimum agar Elbow/Clustering valid untuk parameter K tertentu.
     *
     * Aturan: minimal MIN_ROWS baris, dan minimal k+1 titik (silhouette butuh
     * lebih banyak titik daripada cluster).
     */
    public static function minRequiredRows(int $k): int
    {
        return max(self::MIN_ROWS, $k + 1);
    }

    /**
     * Ambil dataset sebagai list-of-rows sesuai filter & fitur.
     *
     * @param  array<string, mixed>  $filter
     * @return list<array<string, mixed>>
     */
    public function buildDataset(array $filter, array $numericFeatures, array $categoricalFeatures): array
    {
        $this->validateFeatures($numericFeatures, $categoricalFeatures);

        // Selalu SELECT hanya kolom asli (whitelist tetap). Kolom turunan seperti
        // keparahan_score & durasi_lapor_hari dihitung di PHP pada map() di bawah.
        $query = CybercrimeRecord::query()->select(self::REAL_COLUMNS);
        $this->applyFilters($query, $filter);

        return $query->orderBy('id')->get()->map(function (CybercrimeRecord $r) {
            $row = $r->only([
                'id',
                'jenis_kejahatan', 'sub_jenis', 'modus_operandi', 'platform',
                'provinsi', 'tingkat_keparahan', 'jenis_kelamin_korban',
                'pendidikan_korban', 'pekerjaan_korban', 'sumber_data',
                'estimasi_kerugian', 'jumlah_korban', 'usia_korban',
            ]);
            // Derived features — terhitung di sisi PHP agar tidak memerlukan view DB
            $row['keparahan_score'] = $r->keparahan_score;
            $row['durasi_lapor_hari'] = $r->tanggal_kejadian && $r->tanggal_laporan
                ? (int) $r->tanggal_kejadian->diffInDays($r->tanggal_laporan)
                : 0;
            return $row;
        })->all();
    }

    /**
     * Hitung jumlah baris yang cocok dengan filter — tanpa memuat data.
     *
     * Dipakai untuk preview di form analisis agar pengguna tahu apakah subset
     * cukup besar SEBELUM menjalankan Elbow/Clustering. Hanya COUNT(*), murah.
     *
     * @param  array<string, mixed>  $filter
     */
    public function countDataset(array $filter): int
    {
        $query = CybercrimeRecord::query();
        $this->applyFilters($query, $filter);

        return $query->count();
    }

    /**
     * Terapkan filter subset (tanggal, jenis, provinsi) ke query builder.
     *
     * Diekstrak agar buildDataset() dan countDataset() memakai logika filter
     * yang sama persis.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<CybercrimeRecord>  $query
     * @param  array<string, mixed>  $filter
     */
    private function applyFilters($query, array $filter): void
    {
        if (! empty($filter['tanggal_mulai'])) {
            $query->whereDate('tanggal_kejadian', '>=', $filter['tanggal_mulai']);
        }
        if (! empty($filter['tanggal_selesai'])) {
            $query->whereDate('tanggal_kejadian', '<=', $filter['tanggal_selesai']);
        }
        if (! empty($filter['jenis_kejahatan'])) {
            $query->whereIn('jenis_kejahatan', (array) $filter['jenis_kejahatan']);
        }
        if (! empty($filter['provinsi'])) {
            $query->whereIn('provinsi', (array) $filter['provinsi']);
        }
    }

    /**
     * Jalankan Elbow scan untuk menentukan K optimal.
     *
     * @param  array<string, mixed>  $filter
     * @return array{points: list<array>, recommended_k: int, recommendation_reason: string}
     */
    public function elbow(array $filter, array $numericFeatures, array $categoricalFeatures, int $kMin = 2, int $kMax = 10, string $scaler = 'standard'): array
    {
        $dataset = $this->buildDataset($filter, $numericFeatures, $categoricalFeatures);
        $count = count($dataset);

        // ML service mensyaratkan minimal 10 baris; silhouette butuh > kMin titik.
        $minRequired = self::minRequiredRows($kMin);
        if ($count < $minRequired) {
            throw new RuntimeException(
                "Data hasil filter hanya {$count} baris (minimal {$minRequired}). ".
                'Perluas rentang tanggal, pilih lebih banyak jenis/provinsi, atau kurangi K Min.'
            );
        }

        // Clamp kMax agar tidak melebihi data: KMeans butuh n_samples >= k dan
        // idealnya tiap cluster punya >= 2 anggota. floor(count/2) jadi batas wajar.
        $kMax = max($kMin, min($kMax, intdiv($count, 2)));

        return $this->ml->elbow($dataset, [
            'numeric' => $numericFeatures,
            'categorical' => $categoricalFeatures,
            'scaler' => $scaler,
        ], $kMin, $kMax);
    }

    /**
     * Eksekusi clustering dan persist sebagai ClusteringRun.
     *
     * @param  array<string, mixed>  $config  Konfigurasi: nama, deskripsi, n_clusters, fitur, filter, scaler
     */
    public function runAndPersist(array $config, ?int $createdBy = null): ClusteringRun
    {
        $dataset = $this->buildDataset(
            filter: $config['filter'] ?? [],
            numericFeatures: $config['fitur_numerik'] ?? [],
            categoricalFeatures: $config['fitur_kategorikal'] ?? [],
        );
        $count = count($dataset);
        $minRequired = self::minRequiredRows($config['n_clusters']);
        if ($count < $minRequired) {
            throw new RuntimeException(
                "Data hasil filter hanya {$count} baris, tidak cukup untuk K = {$config['n_clusters']} ".
                "(minimal {$minRequired} baris). Perluas filter atau kurangi nilai K."
            );
        }

        $run = ClusteringRun::create([
            'nama' => $config['nama'],
            'deskripsi' => $config['deskripsi'] ?? null,
            'n_clusters' => $config['n_clusters'],
            'fitur_numerik' => $config['fitur_numerik'] ?? [],
            'fitur_kategorikal' => $config['fitur_kategorikal'] ?? [],
            'scaler' => $config['scaler'] ?? 'standard',
            'filter' => $config['filter'] ?? [],
            'random_state' => $config['random_state'] ?? 42,
            'jumlah_data' => count($dataset),
            'mode' => $config['mode'] ?? 'manual',
            'status' => ClusteringRun::STATUS_DRAFT,
            'created_by' => $createdBy,
        ]);

        try {
            $result = $this->ml->cluster(
                data: $dataset,
                features: [
                    'numeric' => $run->fitur_numerik,
                    'categorical' => $run->fitur_kategorikal,
                    'scaler' => $run->scaler,
                ],
                nClusters: $run->n_clusters,
                randomState: $run->random_state,
            );

            DB::transaction(function () use ($run, $result) {
                $run->update([
                    'inertia' => $result['inertia'] ?? null,
                    'silhouette' => $result['metrics']['silhouette'] ?? null,
                    'davies_bouldin' => $result['metrics']['davies_bouldin'] ?? null,
                    'calinski_harabasz' => $result['metrics']['calinski_harabasz'] ?? null,
                    'iterations' => $result['iterations'] ?? null,
                    'hasil_json' => [
                        'profiles' => $result['profiles'] ?? [],
                        'projection' => $result['projection'] ?? [],
                        'feature_importance' => $result['feature_importance'] ?? [],
                        'centroids' => $result['centroids'] ?? [],
                    ],
                    'status' => ClusteringRun::STATUS_SUKSES,
                ]);

                // Index PCA projection by record id untuk join cepat
                $pca = collect($result['projection'] ?? [])->keyBy('id');
                $rows = collect($result['assignments'] ?? [])->map(function ($a) use ($run, $pca) {
                    $proj = $pca->get($a['id'], []);
                    return [
                        'clustering_run_id' => $run->id,
                        'cybercrime_record_id' => $a['id'],
                        'cluster' => $a['cluster'],
                        'pca_x' => $proj['x'] ?? null,
                        'pca_y' => $proj['y'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })->all();

                foreach (array_chunk($rows, 500) as $chunk) {
                    ClusterAssignment::insert($chunk);
                }
            });

            ActivityLog::record('clustering.success', $run, [
                'n_clusters' => $run->n_clusters,
                'silhouette' => $run->silhouette,
            ]);
        } catch (\Throwable $e) {
            $run->update([
                'status' => ClusteringRun::STATUS_GAGAL,
                'error_message' => $e->getMessage(),
            ]);
            ActivityLog::record('clustering.failed', $run, ['error' => $e->getMessage()]);
            throw $e;
        }

        return $run->fresh();
    }

    /**
     * Validasi nama-nama fitur terhadap whitelist (defense-in-depth).
     */
    private function validateFeatures(array $numeric, array $categorical): void
    {
        $invalidN = array_diff($numeric, self::ALLOWED_NUMERIC);
        $invalidC = array_diff($categorical, self::ALLOWED_CATEGORICAL);
        if ($invalidN) {
            throw new RuntimeException('Fitur numerik tidak dikenal: '.implode(', ', $invalidN));
        }
        if ($invalidC) {
            throw new RuntimeException('Fitur kategorikal tidak dikenal: '.implode(', ', $invalidC));
        }
        if (! $numeric && ! $categorical) {
            throw new RuntimeException('Minimal satu fitur (numerik atau kategorikal) harus dipilih.');
        }
    }

    /**
     * Bangun statistik dashboard untuk halaman utama.
     *
     * @return array<string, mixed>
     */
    public function dashboardStats(): array
    {
        $now = now();
        $startYear = $now->copy()->startOfYear();

        $total = CybercrimeRecord::count();
        $tahunIni = CybercrimeRecord::where('tanggal_kejadian', '>=', $startYear)->count();
        $kerugianYtd = (int) CybercrimeRecord::where('tanggal_kejadian', '>=', $startYear)->sum('estimasi_kerugian');
        $jumlahRuns = ClusteringRun::where('status', ClusteringRun::STATUS_SUKSES)->count();

        $perJenis = CybercrimeRecord::query()
            ->selectRaw('jenis_kejahatan, COUNT(*) as total')
            ->groupBy('jenis_kejahatan')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($r) => ['label' => $r->jenis_kejahatan, 'total' => (int) $r->total])
            ->all();

        $perProvinsi = CybercrimeRecord::query()
            ->selectRaw('provinsi, COUNT(*) as total')
            ->groupBy('provinsi')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($r) => ['label' => $r->provinsi, 'total' => (int) $r->total])
            ->all();

        $trenTahunan = CybercrimeRecord::query()
            ->selectRaw('YEAR(tanggal_kejadian) as tahun, COUNT(*) as total, SUM(estimasi_kerugian) as kerugian')
            ->groupBy('tahun')
            ->orderBy('tahun')
            ->get()
            ->map(fn ($r) => [
                'tahun' => (int) $r->tahun,
                'total' => (int) $r->total,
                'kerugian' => (int) $r->kerugian,
            ])
            ->all();

        $trenBulanan = CybercrimeRecord::query()
            ->selectRaw('DATE_FORMAT(tanggal_kejadian, "%Y-%m") as periode, COUNT(*) as total')
            ->where('tanggal_kejadian', '>=', $now->copy()->subMonths(23)->startOfMonth())
            ->groupBy('periode')
            ->orderBy('periode')
            ->get()
            ->map(fn ($r) => ['periode' => (string) $r->periode, 'total' => (int) $r->total])
            ->all();

        return compact('total', 'tahunIni', 'kerugianYtd', 'jumlahRuns', 'perJenis', 'perProvinsi', 'trenTahunan', 'trenBulanan');
    }
}
