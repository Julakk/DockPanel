<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Node extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'public', 'fqdn', 'scheme',
        'behind_proxy', 'maintenance_mode', 'location_id',
        'memory', 'memory_overallocate', 'disk', 'disk_overallocate',
        'daemon_listen', 'daemon_sftp', 'daemon_token',
    ];

    protected $hidden = ['daemon_token'];

    protected function casts(): array
    {
        return [
            'public' => 'boolean',
            'behind_proxy' => 'boolean',
            'maintenance_mode' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Node $node) {
            $node->uuid = (string) Str::uuid();
        });
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function servers()
    {
        return $this->hasMany(Server::class);
    }

    public function allocations()
    {
        return $this->hasMany(Allocation::class);
    }

    public function mounts()
    {
        return $this->belongsToMany(Mount::class, 'mount_node');
    }

    /**
     * Base URL buat hit API Wings di node ini.
     * Contoh: https://node1.ahmadstore.id:8080
     */
    public function daemonBaseUrl(): string
    {
        return "{$this->scheme}://{$this->fqdn}:{$this->daemon_listen}";
    }

    /**
     * Info daemon dari GET /api/system (versi Wings, arsitektur, dll), mirip
     * halaman node Pterodactyl yang nampilin versi Wings. Balikin
     * ['ok' => true, 'data' => [...]] atau ['ok' => false, 'error' => '...'].
     * Timeout pendek biar halaman Node nggak ikut hang kalau Wings mati.
     */
    public function daemonSystemInfo(): array
    {
        try {
            $res = \Illuminate\Support\Facades\Http::withToken($this->daemon_token)
                ->baseUrl($this->daemonBaseUrl())
                ->acceptJson()
                ->timeout(3)
                ->connectTimeout(3)
                ->get('/api/system');

            if (! $res->successful()) {
                return ['ok' => false, 'error' => 'HTTP '.$res->status().' dari Wings (cek daemon_token di config.json)'];
            }

            return ['ok' => true, 'data' => $res->json()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => self::explainWingsError($e->getMessage(), $this->scheme)];
        }
    }

    /**
     * Terjemahin error koneksi ke Wings jadi petunjuk yang bisa langsung dipakai.
     * Error paling umum: scheme Node (https/http) nggak cocok sama Wings.
     */
    public static function explainWingsError(string $message, string $scheme): string
    {
        if (str_contains($message, 'cURL error 35') || str_contains($message, 'wrong version number')) {
            return $scheme === 'https'
                ? 'Scheme Node diset https, tapi Wings melayani HTTP biasa. Ubah scheme Node ke http, atau aktifin ssl di config.json Wings. ('.$message.')'
                : 'Handshake TLS gagal. ('.$message.')';
        }

        if (str_contains($message, 'cURL error 7') || str_contains($message, 'Connection refused')) {
            return 'Wings nggak bisa dijangkau. Cek service wings jalan dan port daemon kebuka di firewall. ('.$message.')';
        }

        if (str_contains($message, 'cURL error 28') || str_contains($message, 'timed out')) {
            return 'Wings nggak merespon (timeout). Cek firewall/port daemon. ('.$message.')';
        }

        return $message;
    }

    public function memoryUsed(): int
    {
        return (int) $this->servers()->sum('memory');
    }

    public function diskUsed(): int
    {
        return (int) $this->servers()->sum('disk');
    }
}
