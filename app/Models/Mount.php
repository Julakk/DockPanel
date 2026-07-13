<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mount extends Model
{
    protected $fillable = ['name', 'description', 'source', 'target', 'read_only'];

    protected function casts(): array
    {
        return ['read_only' => 'boolean'];
    }

    public function nodes()
    {
        return $this->belongsToMany(Node::class, 'mount_node');
    }
}
