<?php

namespace Database\Seeders;

use App\Models\CybercrimeRecord;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder data sintetik cybercrime Indonesia 2019–2024.
 *
 * Distribusi (jenis kejahatan, provinsi, modus) didesain mendekati pola yang
 * dilaporkan pada publikasi tahunan Patroli Siber Polri dan publikasi
 * akademik, namun nilai-nilai numerik diacak sehingga TIDAK merepresentasikan
 * kasus nyata. Tujuan: menyediakan dataset yang cukup beragam untuk
 * mendemonstrasikan algoritma K-Means.
 *
 * Setelah `db:seed` dijalankan, sekitar 1.500 record tersedia.
 */
class CybercrimeSeeder extends Seeder
{
    public function run(): void
    {
        if (CybercrimeRecord::count() > 0) {
            return;
        }

        $rng = new \Random\Randomizer(new \Random\Engine\Mt19937(42));

        // Distribusi jenis kejahatan (bobot kira-kira merefleksikan publikasi resmi)
        $jenis = [
            'Penipuan Online'   => ['weight' => 35, 'sub' => ['Investasi Bodong', 'Jual-Beli Fiktif', 'Penipuan Pinjol Ilegal', 'Skema Ponzi']],
            'Pencurian Data'    => ['weight' => 12, 'sub' => ['Data Pribadi', 'Data Korporat', 'Data Pemerintah']],
            'Akses Ilegal'      => ['weight' => 10, 'sub' => ['Defacement', 'Brute Force', 'SQL Injection', 'XSS']],
            'Konten Asusila'    => ['weight' => 9,  'sub' => ['Penyebaran Konten', 'Pornografi Anak', 'Sextortion']],
            'Pencemaran Nama'   => ['weight' => 8,  'sub' => ['Doxing', 'Hate Speech', 'Fitnah']],
            'Judi Online'       => ['weight' => 8,  'sub' => ['Slot Online', 'Sportsbook', 'Live Casino']],
            'Hoaks/Disinformasi' => ['weight' => 6, 'sub' => ['Hoaks Politik', 'Hoaks Kesehatan', 'SARA']],
            'Peretasan Akun'    => ['weight' => 6,  'sub' => ['Phishing', 'Sim Swap', 'Social Engineering']],
            'Pemerasan Siber'   => ['weight' => 4,  'sub' => ['Ransomware', 'Sextortion Bisnis']],
            'Penyebaran Malware' => ['weight' => 2, 'sub' => ['Trojan', 'Spyware', 'Worm']],
        ];

        $modus = [
            'Penipuan Online'    => ['Phishing', 'Social Engineering', 'Fake Marketplace', 'Modus Skema Ponzi', 'Telepon Penipuan'],
            'Pencurian Data'     => ['SQL Injection', 'Phishing', 'Insider Threat', 'Credential Stuffing'],
            'Akses Ilegal'       => ['Brute Force', 'SQL Injection', 'Exploit Vulnerability', 'Default Credentials'],
            'Konten Asusila'     => ['Penyebaran Massal', 'Sextortion', 'Pemerasan'],
            'Pencemaran Nama'    => ['Akun Anonim', 'Bot Spam', 'Manipulasi Foto'],
            'Judi Online'        => ['Aplikasi Pihak Ketiga', 'Web Ilegal', 'Telegram'],
            'Hoaks/Disinformasi' => ['Forward Massal', 'Akun Buzzer', 'Manipulasi Foto'],
            'Peretasan Akun'     => ['Phishing', 'Sim Swap', 'Social Engineering', 'Password Lemah'],
            'Pemerasan Siber'    => ['Ransomware', 'Sextortion Bisnis', 'DDoS Threat'],
            'Penyebaran Malware' => ['Email Lampiran', 'Software Bajakan', 'USB Drop'],
        ];

        $platform = ['WhatsApp', 'Instagram', 'Facebook', 'Telegram', 'Email', 'Website', 'TikTok', 'Twitter/X', 'Marketplace', 'SMS', 'Telepon'];

        $provinsi = [
            'DKI Jakarta'         => ['weight' => 18, 'lat' => -6.2088, 'lng' => 106.8456],
            'Jawa Barat'          => ['weight' => 14, 'lat' => -6.9147, 'lng' => 107.6098],
            'Jawa Timur'          => ['weight' => 11, 'lat' => -7.2575, 'lng' => 112.7521],
            'Jawa Tengah'         => ['weight' => 9,  'lat' => -6.9667, 'lng' => 110.4167],
            'Banten'              => ['weight' => 6,  'lat' => -6.1198, 'lng' => 106.1503],
            'Sumatera Utara'      => ['weight' => 6,  'lat' => 3.5952,  'lng' => 98.6722],
            'Sulawesi Selatan'    => ['weight' => 5,  'lat' => -5.1477, 'lng' => 119.4327],
            'Bali'                => ['weight' => 4,  'lat' => -8.4095, 'lng' => 115.1889],
            'Sumatera Selatan'    => ['weight' => 4,  'lat' => -2.9909, 'lng' => 104.7567],
            'Daerah Istimewa Yogyakarta' => ['weight' => 4, 'lat' => -7.7972, 'lng' => 110.3688],
            'Riau'                => ['weight' => 3,  'lat' => 0.5071,  'lng' => 101.4478],
            'Sumatera Barat'      => ['weight' => 3,  'lat' => -0.9471, 'lng' => 100.4172],
            'Lampung'             => ['weight' => 3,  'lat' => -5.4500, 'lng' => 105.2667],
            'Kalimantan Timur'    => ['weight' => 3,  'lat' => 0.5071,  'lng' => 117.1536],
            'Kalimantan Selatan'  => ['weight' => 3,  'lat' => -3.3194, 'lng' => 114.5908],
            'Sulawesi Utara'      => ['weight' => 2,  'lat' => 1.4748,  'lng' => 124.8421],
            'Nusa Tenggara Barat' => ['weight' => 2,  'lat' => -8.5833, 'lng' => 116.1167],
            'Papua'               => ['weight' => 1,  'lat' => -2.5337, 'lng' => 140.7181],
        ];

        $pekerjaan = ['Karyawan Swasta', 'Wiraswasta', 'PNS', 'Mahasiswa', 'Pelajar', 'IRT', 'Petani', 'Buruh', 'Profesional', 'Pensiunan', 'Tidak Bekerja'];
        $pendidikan = ['SD', 'SMP', 'SMA', 'D3', 'S1', 'S2', 'S3', 'TD'];
        $sumber = ['Laporan Masyarakat', 'Patroli Siber', 'Aduan BSSN', 'Aduan Konten Kominfo', 'Inisiatif Polri', 'Kerjasama Antar Lembaga'];

        $jenisKeys = array_keys($jenis);
        $jenisWeights = array_map(fn ($j) => $j['weight'], $jenis);
        $provinsiKeys = array_keys($provinsi);
        $provinsiWeights = array_map(fn ($p) => $p['weight'], $provinsi);

        // Generate ~1500 records spread over 2019..2024 dengan tren naik
        $records = [];
        $perTahun = ['2019' => 150, '2020' => 200, '2021' => 240, '2022' => 280, '2023' => 320, '2024' => 360];
        $counter = 1;
        $now = Carbon::now();

        foreach ($perTahun as $tahun => $jumlah) {
            for ($i = 0; $i < $jumlah; $i++) {
                $jk = $this->weighted($rng, $jenisKeys, $jenisWeights);
                $prov = $this->weighted($rng, $provinsiKeys, $provinsiWeights);
                $tanggalKejadian = Carbon::create((int) $tahun, $rng->getInt(1, 12), $rng->getInt(1, 28));
                if ($tanggalKejadian->isAfter($now)) {
                    continue;
                }
                $delayHari = $rng->getInt(0, 30);

                // Profil kerugian, korban, dan keparahan berkorelasi dengan jenis (membentuk cluster alami)
                [$kerugian, $korban, $keparahan] = $this->profilDampak($rng, $jk);

                $records[] = [
                    'nomor_laporan' => sprintf('LP/%s/%04d/SBR', $tahun, $counter++),
                    'tanggal_kejadian' => $tanggalKejadian->toDateString(),
                    'tanggal_laporan' => $tanggalKejadian->copy()->addDays($delayHari)->toDateString(),
                    'jenis_kejahatan' => $jk,
                    'sub_jenis' => $this->pickOne($rng, $jenis[$jk]['sub']),
                    'modus_operandi' => $this->pickOne($rng, $modus[$jk]),
                    'platform' => $this->pickOne($rng, $platform),
                    'provinsi' => $prov,
                    'kota_kabupaten' => null,
                    'latitude' => $provinsi[$prov]['lat'] + ($rng->getInt(-50, 50) / 1000),
                    'longitude' => $provinsi[$prov]['lng'] + ($rng->getInt(-50, 50) / 1000),
                    'usia_korban' => $rng->getInt(17, 65),
                    'jenis_kelamin_korban' => $this->weighted($rng, ['L', 'P', 'TD'], [48, 50, 2]),
                    'pekerjaan_korban' => $this->pickOne($rng, $pekerjaan),
                    'pendidikan_korban' => $this->pickOne($rng, $pendidikan),
                    'estimasi_kerugian' => $kerugian,
                    'jumlah_korban' => $korban,
                    'tingkat_keparahan' => $keparahan,
                    'status_kasus' => $this->weighted($rng, ['baru', 'dalam_penyelidikan', 'p21', 'selesai', 'dihentikan'], [25, 35, 15, 15, 10]),
                    'tersangka_teridentifikasi' => $rng->getInt(0, 99) < 35,
                    'sumber_data' => $this->pickOne($rng, $sumber),
                    'keterangan' => null,
                    'input_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($records, 500) as $chunk) {
            DB::table('cybercrime_records')->insert($chunk);
        }
    }

    /**
     * Pilih satu nilai acak dari array (sequential).
     *
     * @param  array<int, string>  $values
     */
    private function pickOne(\Random\Randomizer $rng, array $values): string
    {
        return $values[$rng->getInt(0, count($values) - 1)];
    }

    /**
     * Pilih elemen acak dengan bobot.
     *
     * Tahan terhadap kombinasi key sequential/asosiatif: kedua array
     * di-reindex dulu agar pasangan items[$i] dan weights[$i] sinkron.
     *
     * @param  array<int|string, string>  $items
     * @param  array<int|string, int>     $weights
     */
    private function weighted(\Random\Randomizer $rng, array $items, array $weights): string
    {
        $items = array_values($items);
        $weights = array_values($weights);
        $total = array_sum($weights);
        $pick = $rng->getInt(1, $total);
        $cum = 0;
        foreach ($items as $i => $item) {
            $cum += $weights[$i];
            if ($pick <= $cum) {
                return $item;
            }
        }
        return end($items);
    }

    /**
     * Hasilkan tuple [kerugian, jumlah_korban, tingkat_keparahan] yang plausible.
     *
     * Korelasi yang sengaja dibuat agar K-Means dapat menemukan struktur:
     *   - Pemerasan Siber / Pencurian Data → kerugian dan keparahan tinggi
     *   - Penipuan Online / Judi Online    → kerugian sedang, jumlah korban tinggi
     *   - Pencemaran Nama / Hoaks          → kerugian rendah, keparahan rendah-sedang
     */
    private function profilDampak(\Random\Randomizer $rng, string $jenis): array
    {
        return match ($jenis) {
            'Pemerasan Siber' => [
                $rng->getInt(50_000_000, 500_000_000),
                $rng->getInt(1, 5),
                $this->weighted($rng, ['sedang', 'tinggi', 'kritis'], [10, 60, 30]),
            ],
            'Pencurian Data' => [
                $rng->getInt(10_000_000, 200_000_000),
                $rng->getInt(1, 50),
                $this->weighted($rng, ['sedang', 'tinggi', 'kritis'], [20, 60, 20]),
            ],
            'Penipuan Online' => [
                $rng->getInt(500_000, 50_000_000),
                $rng->getInt(1, 8),
                $this->weighted($rng, ['rendah', 'sedang', 'tinggi'], [30, 55, 15]),
            ],
            'Judi Online' => [
                $rng->getInt(1_000_000, 30_000_000),
                $rng->getInt(1, 3),
                $this->weighted($rng, ['rendah', 'sedang'], [60, 40]),
            ],
            'Peretasan Akun' => [
                $rng->getInt(1_000_000, 25_000_000),
                $rng->getInt(1, 3),
                $this->weighted($rng, ['sedang', 'tinggi'], [70, 30]),
            ],
            'Akses Ilegal' => [
                $rng->getInt(5_000_000, 100_000_000),
                $rng->getInt(1, 10),
                $this->weighted($rng, ['sedang', 'tinggi'], [55, 45]),
            ],
            'Penyebaran Malware' => [
                $rng->getInt(2_000_000, 80_000_000),
                $rng->getInt(1, 20),
                $this->weighted($rng, ['sedang', 'tinggi', 'kritis'], [30, 55, 15]),
            ],
            'Konten Asusila' => [
                $rng->getInt(0, 5_000_000),
                $rng->getInt(1, 3),
                $this->weighted($rng, ['sedang', 'tinggi'], [60, 40]),
            ],
            'Pencemaran Nama' => [
                $rng->getInt(0, 2_000_000),
                $rng->getInt(1, 2),
                $this->weighted($rng, ['rendah', 'sedang'], [70, 30]),
            ],
            'Hoaks/Disinformasi' => [
                $rng->getInt(0, 1_000_000),
                $rng->getInt(1, 100),
                $this->weighted($rng, ['rendah', 'sedang'], [75, 25]),
            ],
            default => [
                $rng->getInt(0, 10_000_000),
                $rng->getInt(1, 3),
                'sedang',
            ],
        };
    }
}
