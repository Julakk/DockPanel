<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Backup extends Model
{
    public const STATUSES = ['creating', 'completed', 'failed'];

    protected $fillable = [
        'uuid', 'server_id', 'name', 'status', 'size', 'checksum', 'error', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    /** Kelas CSS badge status (lihat .status-* di stylesheet). */
    public function badgeClass(): string
    {
        return match ($this->status) {
            'completed' => 'status-active',
            'creating' => 'status-installing',
            default => 'status-failed',
        };
    }

    /** Ukuran backup dalam bentuk yang enak dibaca, ex: "17,1 MB". */
    public function sizeForHumans(): string
    {
        $bytes = (float) $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return number_format($bytes, $i === 0 ? 0 : 1, ',', '.').' '.$units[$i];
    }
}
