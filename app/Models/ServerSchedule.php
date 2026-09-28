<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerSchedule extends Model
{
    public const ACTIONS = ['start', 'stop', 'restart', 'kill', 'command'];

    protected $fillable = [
        'server_id', 'name', 'action', 'payload', 'cron',
        'is_active', 'last_run_at', 'last_status',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
        ];
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }
}
