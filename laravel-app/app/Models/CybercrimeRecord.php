<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Representasi laporan tindak pidana siber. Merupakan unit data utama yang
 * akan di-cluster.
 */
class CybercrimeRecord extends Model
{
    use HasFactory, SoftDeletes;

    public const TINGKAT_KEPARAHAN = ['rendah', 'sedang', 'tinggi', 'kritis'];
    public const STATUS_KASUS = ['baru', 'dalam_penyelidikan', 'p21', 'selesai', 'dihentikan'];

    protected $fillable = [
        'nomor_laporan',
        'tanggal_kejadian',
        'tanggal_laporan',
        'jenis_kejahatan',
        'sub_jenis',
        'modus_operandi',
        'platform',
        'provinsi',
        'kota_kabupaten',
        'latitude',
        'longitude',
        'usia_korban',
        'jenis_kelamin_korban',
        'pekerjaan_korban',
        'pendidikan_korban',
        'estimasi_kerugian',
        'jumlah_korban',
        'tingkat_keparahan',
        'status_kasus',
        'tersangka_teridentifikasi',
        'sumber_data',
        'keterangan',
        'input_by',
    ];

    protected $casts = [
        'tanggal_kejadian' => 'date',
        'tanggal_laporan' => 'date',
        'usia_korban' => 'integer',
        'estimasi_kerugian' => 'integer',
        'jumlah_korban' => 'integer',
        'tersangka_teridentifikasi' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function inputBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'input_by');
    }

    public function clusterAssignments(): HasMany
    {
        return $this->hasMany(ClusterAssignment::class);
    }

    public function scopeBetweenDates(Builder $q, ?string $start, ?string $end): Builder
    {
        if ($start) {
            $q->whereDate('tanggal_kejadian', '>=', $start);
        }
        if ($end) {
            $q->whereDate('tanggal_kejadian', '<=', $end);
        }
        return $q;
    }

    public function scopeOfJenis(Builder $q, string|array $jenis): Builder
    {
        return $q->whereIn('jenis_kejahatan', (array) $jenis);
    }

    public function scopeOfProvinsi(Builder $q, string|array $provinsi): Builder
    {
        return $q->whereIn('provinsi', (array) $provinsi);
    }

    public function getKeparahanScoreAttribute(): int
    {
        return match ($this->tingkat_keparahan) {
            'rendah' => 1,
            'sedang' => 2,
            'tinggi' => 3,
            'kritis' => 4,
            default => 2,
        };
    }
}
