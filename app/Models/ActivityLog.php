<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = true;

    protected $fillable = ['user_id', 'server_id', 'event', 'metadata', 'ip'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Catat satu aktivitas. Dipanggil dari controller manapun yang butuh nyatet histori.
     *
     * Contoh: ActivityLog::record('auth:success')
     *         ActivityLog::record('server:power', ['action' => 'start'], $server)
     */
    public static function record(string $event, array $metadata = [], ?Server $server = null): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'server_id' => $server?->id,
            'event' => $event,
            'metadata' => $metadata,
            'ip' => request()->ip(),
        ]);
    }
}
