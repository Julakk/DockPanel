<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Server extends Model
{
    protected $fillable = [
        'name', 'description', 'suspended',
        'owner_id', 'node_id', 'nest_id', 'egg_id',
        'memory', 'swap', 'disk', 'io', 'cpu', 'threads',
        'startup', 'image', 'skip_scripts', 'status',
    ];

    protected function casts(): array
    {
        return [
            'suspended' => 'boolean',
            'skip_scripts' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Server $server) {
            $server->uuid = (string) Str::uuid();
            $server->uuid_short = substr(str_replace('-', '', $server->uuid), 0, 8);
        });
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function node()
    {
        return $this->belongsTo(Node::class);
    }

    public function nest()
    {
        return $this->belongsTo(Nest::class);
    }

    public function egg()
    {
        return $this->belongsTo(Egg::class);
    }

    public function allocations()
    {
        return $this->hasMany(Allocation::class);
    }

    public function primaryAllocation()
    {
        return $this->hasOne(Allocation::class)->where('is_primary', true);
    }

    public function serverVariables()
    {
        return $this->hasMany(ServerVariable::class);
    }

    public function subusers()
    {
        return $this->belongsToMany(User::class, 'server_subusers')
            ->withPivot('permissions')
            ->withTimestamps();
    }

    public function databases()
    {
        return $this->hasMany(ServerDatabase::class);
    }

    public function mounts()
    {
        return $this->belongsToMany(Mount::class, 'mount_server');
    }

    /**
     * Docker container name di Wings, konsisten pakai uuid.
     */
    public function containerName(): string
    {
        return "dockpanel-{$this->uuid}";
    }
}
