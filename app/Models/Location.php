<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = ['short_code', 'description'];

    public function nodes()
    {
        return $this->hasMany(Node::class);
    }
}
