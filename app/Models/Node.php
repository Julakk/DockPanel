<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Node extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'public', 'fqdn', 'scheme',
        'behind_proxy', 'maintenance_mode',
        'memory', 'memory_overallocate', 'disk', 'disk_overallocate',
        'daemon_listen', 'daemon_sftp', 'daemon_token',
    ];

    protected $hidden = ['daemon_token'];

    protected function casts(): array
    {
        return [
            'public' => 'boolean',
            'behind_proxy' => 'boolean',
            'maintenance_mode' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Node $node) {
            $node->uuid = (string) Str::uuid();
        });
    }

    public function servers()
    {
        return $this->hasMany(Server::class);
    }

    public function allocations()
    {
        return $this->hasMany(Allocation::class);
    }

    /**
     * Base URL buat hit API Wings di node ini.
     * Contoh: https://node1.ahmadstore.id:8080
     */
    public function daemonBaseUrl(): string
    {
        return "{$this->scheme}://{$this->fqdn}:{$this->daemon_listen}";
    }

    public function memoryUsed(): int
    {
        return (int) $this->servers()->sum('memory');
    }

    public function diskUsed(): int
    {
        return (int) $this->servers()->sum('disk');
    }
}
