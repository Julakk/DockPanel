<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'root_admin',
        'language',
        'two_factor_secret',
        'two_factor_enabled_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'root_admin' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_enabled_at' => 'datetime',
        ];
    }

    public function servers()
    {
        return $this->hasMany(Server::class, 'owner_id');
    }

    public function subuserOfServers()
    {
        return $this->belongsToMany(Server::class, 'server_subusers')
            ->withPivot('permissions')
            ->withTimestamps();
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function isRootAdmin(): bool
    {
        return $this->root_admin;
    }

    public function hasTwoFactorEnabled(): bool
    {
        return ! is_null($this->two_factor_enabled_at);
    }
}
