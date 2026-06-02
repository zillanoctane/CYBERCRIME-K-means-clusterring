<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'aksi', 'subjek_type', 'subjek_id', 'metadata', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $aksi, ?Model $subjek = null, array $metadata = []): void
    {
        static::create([
            'user_id' => auth()->id(),
            'aksi' => $aksi,
            'subjek_type' => $subjek?->getMorphClass(),
            'subjek_id' => $subjek?->getKey(),
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 255),
        ]);
    }
}
