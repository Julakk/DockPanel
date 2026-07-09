<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Egg extends Model
{
    protected $fillable = [
        'nest_id', 'name', 'description',
        'docker_image', 'docker_images',
        'startup', 'config_files', 'config_startup', 'config_stop',
        'script_container', 'script_install',
    ];

    protected function casts(): array
    {
        return [
            'docker_images' => 'array',
            'config_files' => 'array',
            'config_startup' => 'array',
            'config_stop' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Egg $egg) {
            $egg->uuid = (string) Str::uuid();
        });
    }

    public function nest()
    {
        return $this->belongsTo(Nest::class);
    }

    public function variables()
    {
        return $this->hasMany(EggVariable::class);
    }

    public function servers()
    {
        return $this->hasMany(Server::class);
    }

    /**
     * Render startup command dengan variable dari server tertentu.
     * Ganti placeholder {{VAR_NAME}} jadi nilai aslinya.
     */
    public function renderStartup(Server $server): string
    {
        $command = $this->startup;

        foreach ($server->serverVariables()->with('eggVariable')->get() as $sv) {
            $command = str_replace(
                '{{'.$sv->eggVariable->env_variable.'}}',
                $sv->variable_value ?? $sv->eggVariable->default_value,
                $command
            );
        }

        return $command;
    }
}
