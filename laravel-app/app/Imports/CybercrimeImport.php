<?php

namespace App\Imports;

use App\Models\CybercrimeRecord;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Importer untuk dataset cybercrime dari CSV/XLSX.
 *
 * Format header yang dikenali (lihat ``data/sample-cybercrime.csv``):
 *   nomor_laporan, tanggal_kejadian, tanggal_laporan, jenis_kejahatan, sub_jenis,
 *   modus_operandi, platform, provinsi, kota_kabupaten, latitude, longitude,
 *   usia_korban, jenis_kelamin_korban, pekerjaan_korban, pendidikan_korban,
 *   estimasi_kerugian, jumlah_korban, tingkat_keparahan, status_kasus,
 *   tersangka_teridentifikasi, sumber_data, keterangan
 */
class CybercrimeImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    private int $imported = 0;
    private array $errors = [];

    public function __construct(private readonly ?int $inputBy = null) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $i => $row) {
            try {
                $payload = [
                    'nomor_laporan' => (string) $row['nomor_laporan'],
                    'tanggal_kejadian' => $this->parseDate($row['tanggal_kejadian']),
                    'tanggal_laporan' => $this->parseDate($row['tanggal_laporan'] ?? $row['tanggal_kejadian']),
                    'jenis_kejahatan' => (string) $row['jenis_kejahatan'],
                    'sub_jenis' => $row['sub_jenis'] ?? null,
                    'modus_operandi' => (string) ($row['modus_operandi'] ?? 'Tidak Diketahui'),
                    'platform' => $row['platform'] ?? null,
                    'provinsi' => (string) $row['provinsi'],
                    'kota_kabupaten' => $row['kota_kabupaten'] ?? null,
                    'latitude' => $row['latitude'] !== null && $row['latitude'] !== '' ? (float) $row['latitude'] : null,
                    'longitude' => $row['longitude'] !== null && $row['longitude'] !== '' ? (float) $row['longitude'] : null,
                    'usia_korban' => $row['usia_korban'] !== null && $row['usia_korban'] !== '' ? (int) $row['usia_korban'] : null,
                    'jenis_kelamin_korban' => $this->normalize($row['jenis_kelamin_korban'] ?? 'TD', ['L', 'P', 'TD']),
                    'pekerjaan_korban' => $row['pekerjaan_korban'] ?? null,
                    'pendidikan_korban' => $this->normalize($row['pendidikan_korban'] ?? 'TD', ['SD', 'SMP', 'SMA', 'D3', 'S1', 'S2', 'S3', 'TD']),
                    'estimasi_kerugian' => (int) ($row['estimasi_kerugian'] ?? 0),
                    'jumlah_korban' => max(1, (int) ($row['jumlah_korban'] ?? 1)),
                    'tingkat_keparahan' => $this->normalize($row['tingkat_keparahan'] ?? 'sedang', ['rendah', 'sedang', 'tinggi', 'kritis']),
                    'status_kasus' => $this->normalize($row['status_kasus'] ?? 'baru', ['baru', 'dalam_penyelidikan', 'p21', 'selesai', 'dihentikan']),
                    'tersangka_teridentifikasi' => filter_var($row['tersangka_teridentifikasi'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'sumber_data' => $row['sumber_data'] ?? 'Laporan Masyarakat',
                    'keterangan' => $row['keterangan'] ?? null,
                    'input_by' => $this->inputBy,
                ];

                CybercrimeRecord::updateOrCreate(['nomor_laporan' => $payload['nomor_laporan']], $payload);
                $this->imported++;
            } catch (\Throwable $e) {
                $this->errors[] = ['baris' => $i + 2, 'pesan' => $e->getMessage()];
            }
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function getImportedCount(): int
    {
        return $this->imported;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    private function parseDate(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }
        if (is_numeric($value)) {
            // Format tanggal Excel serial
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }
        return Carbon::parse((string) $value)->toDateString();
    }

    private function normalize(string $value, array $allowed): string
    {
        $v = strtolower(trim($value));
        foreach ($allowed as $opt) {
            if (strtolower($opt) === $v) {
                return $opt;
            }
        }
        return $allowed[count($allowed) - 1]; // default ke 'TD' / nilai netral
    }
}
