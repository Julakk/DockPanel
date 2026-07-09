<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Nest extends Model
{
    protected $fillable = ['name', 'description'];

    protected static function booted(): void
    {
        static::creating(function (Nest $nest) {
            $nest->uuid = (string) Str::uuid();
        });
    }

    public function eggs()
    {
        return $this->hasMany(Egg::class);
    }
}
