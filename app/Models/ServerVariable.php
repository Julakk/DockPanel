<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerVariable extends Model
{
    protected $fillable = ['server_id', 'egg_variable_id', 'variable_value'];

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function eggVariable()
    {
        return $this->belongsTo(EggVariable::class);
    }
}
