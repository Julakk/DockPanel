<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatabaseHost extends Model
{
    protected $fillable = ['name', 'host', 'public_host', 'port', 'username', 'password', 'node_id'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
        ];
    }

    public function node()
    {
        return $this->belongsTo(Node::class);
    }

    public function databases()
    {
        return $this->hasMany(ServerDatabase::class);
    }

    /** Alamat buat game server / klien: public_host kalau diisi, kalau nggak host biasa. */
    public function endpoint(): string
    {
        return $this->public_host ?: $this->host;
    }

    /**
     * Host yang dipakai buat database baru milik $server: yang terikat ke node
     * server itu dulu, kalau nggak ada pakai host yang nggak terikat node mana pun.
     */
    public static function forServer(Server $server): ?self
    {
        return static::where('node_id', $server->node_id)->orderBy('id')->first()
            ?? static::whereNull('node_id')->orderBy('id')->first();
    }
}
