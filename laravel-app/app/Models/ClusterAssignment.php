<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClusterAssignment extends Model
{
    protected $fillable = [
        'clustering_run_id',
        'cybercrime_record_id',
        'cluster',
        'pca_x',
        'pca_y',
    ];

    protected $casts = [
        'cluster' => 'integer',
        'pca_x' => 'float',
        'pca_y' => 'float',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(ClusteringRun::class, 'clustering_run_id');
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(CybercrimeRecord::class, 'cybercrime_record_id');
    }
}
