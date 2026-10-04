<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerDatabase extends Model
{
    protected $fillable = ['server_id', 'database_host_id', 'database', 'username', 'password', 'remote'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
        ];
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function databaseHost()
    {
        return $this->belongsTo(DatabaseHost::class);
    }

    /** "alamat:port" yang dipakai klien / game server buat konek. */
    public function endpoint(): string
    {
        $host = $this->databaseHost;

        return $host ? $host->endpoint().':'.$host->port : '-';
    }

    /** JDBC string gaya Pterodactyl: jdbc:mysql://user:pass@host:port/db */
    public function jdbcUrl(): string
    {
        return 'jdbc:mysql://'.rawurlencode($this->username).':'.rawurlencode((string) $this->password).'@'.$this->endpoint().'/'.$this->database;
    }
}
