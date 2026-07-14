<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatabaseHost extends Model
{
    protected $fillable = ['name', 'host', 'port', 'username', 'password', 'node_id'];

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
}
