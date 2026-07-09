<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Allocation extends Model
{
    protected $fillable = ['node_id', 'ip', 'ip_alias', 'port', 'server_id', 'is_primary'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function node()
    {
        return $this->belongsTo(Node::class);
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function isAssigned(): bool
    {
        return $this->server_id !== null;
    }
}
