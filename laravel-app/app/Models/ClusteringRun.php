<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sebuah eksekusi K-Means clustering — beserta parameter dan hasilnya.
 */
class ClusteringRun extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUKSES = 'sukses';
    public const STATUS_GAGAL = 'gagal';

    protected $fillable = [
        'nama',
        'deskripsi',
        'n_clusters',
        'fitur_numerik',
        'fitur_kategorikal',
        'scaler',
        'filter',
        'random_state',
        'jumlah_data',
        'inertia',
        'silhouette',
        'davies_bouldin',
        'calinski_harabasz',
        'iterations',
        'hasil_json',
        'mode',
        'elbow_points',
        'status',
        'error_message',
        'created_by',
    ];

    protected $casts = [
        'fitur_numerik' => 'array',
        'fitur_kategorikal' => 'array',
        'filter' => 'array',
        'hasil_json' => 'array',
        'elbow_points' => 'array',
        'n_clusters' => 'integer',
        'jumlah_data' => 'integer',
        'random_state' => 'integer',
        'iterations' => 'integer',
        'inertia' => 'float',
        'silhouette' => 'float',
        'davies_bouldin' => 'float',
        'calinski_harabasz' => 'float',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ClusterAssignment::class);
    }

    public function getProfilesAttribute(): array
    {
        return $this->hasil_json['profiles'] ?? [];
    }

    public function getProjectionAttribute(): array
    {
        return $this->hasil_json['projection'] ?? [];
    }

    public function getFeatureImportanceAttribute(): array
    {
        return $this->hasil_json['feature_importance'] ?? [];
    }

    public function qualityLabel(): string
    {
        $sil = $this->silhouette ?? 0.0;
        return match (true) {
            $sil >= 0.70 => 'Sangat Baik',
            $sil >= 0.50 => 'Baik',
            $sil >= 0.25 => 'Cukup',
            default => 'Lemah',
        };
    }
}
