<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EggVariable extends Model
{
    protected $fillable = [
        'egg_id', 'name', 'env_variable', 'description',
        'default_value', 'user_viewable', 'user_editable', 'rules',
    ];

    protected function casts(): array
    {
        return [
            'user_viewable' => 'boolean',
            'user_editable' => 'boolean',
        ];
    }

    public function egg()
    {
        return $this->belongsTo(Egg::class);
    }
}
