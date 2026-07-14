<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerDatabase extends Model
{
    protected $fillable = ['server_id', 'database_host_id', 'database', 'username', 'password'];

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
}
