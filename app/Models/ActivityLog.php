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

    public function label(): string
    {
        $action = is_array($this->metadata) ? ($this->metadata['action'] ?? null) : null;

        return $this->event.(! empty($action) ? '.'.$action : '');
    }

    public function icon(): string
    {
        $ev = $this->label();
        $map = [
            'power.start' => '▶️', 'power.stop' => '⏹️', 'power.restart' => '🔄', 'power.kill' => '💀',
            'backup.create' => '💾', 'backup.restore' => '♻️', 'backup.delete' => '🗑️',
            'file.upload' => '⬆️', 'file.delete' => '🗑️', 'file.write' => '✏️',
            'file.extract' => '📦', 'file.chmod' => '🔒', 'file.' => '📄',
            'reinstall' => '🔧', 'install-status' => '⚙️',
            'database' => '🗄️', 'subuser' => '👥', 'allocation' => '🌐',
        ];
        foreach ($map as $needle => $icon) {
            if (str_contains($ev, $needle)) {
                return $icon;
            }
        }

        return '📋';
    }
}
