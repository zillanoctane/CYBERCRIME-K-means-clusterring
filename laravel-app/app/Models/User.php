<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_ANALIS = 'analis';
    public const ROLE_VIEWER = 'viewer';

    protected $fillable = [
        'name', 'email', 'password', 'role', 'instansi', 'is_active', 'last_login_at',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function clusteringRuns(): HasMany
    {
        return $this->hasMany(ClusteringRun::class, 'created_by');
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isAnalis(): bool
    {
        return $this->role === self::ROLE_ANALIS;
    }

    public function canAnalyze(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_ANALIS], true);
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_ANALIS => 'Analis Data',
            self::ROLE_VIEWER => 'Pengamat',
            default => ucfirst((string) $this->role),
        };
    }
}
