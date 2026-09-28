<?php

namespace App\Services;

use App\Models\Server;
use Illuminate\Support\Facades\Http;

/**
 * Ambil utilisasi server dari Wings. Kalau Wings belum aktif / gagal,
 * balikin nilai kosong dengan source=mock supaya UI tetap jalan.
 */
class ServerResourceService
{
    public function for(Server $server): array
    {
        $base = [
            'state' => 'unknown',
            'cpu_percent' => 0,
            'memory_bytes' => 0,
            'memory_limit_bytes' => (int) $server->memory * 1024 * 1024,
            'disk_bytes' => 0,
            'disk_limit_bytes' => (int) $server->disk * 1024 * 1024,
            'source' => 'mock',
        ];

        try {
            $node = $server->node;
            if (! $node) {
                return $base;
            }

            $response = Http::withToken($node->daemon_token)
                ->baseUrl($node->daemonBaseUrl())
                ->acceptJson()
                ->timeout(2)
                ->get("/api/servers/{$server->uuid}/resources");

            if (! $response->successful()) {
                return $base;
            }

            $j = $response->json() ?? [];

            return array_merge($base, [
                'state' => data_get($j, 'current_state', data_get($j, 'state', 'unknown')),
                'cpu_percent' => round((float) data_get($j, 'utilization.cpu_absolute', data_get($j, 'cpu_absolute', 0)), 1),
                'memory_bytes' => (int) data_get($j, 'utilization.memory_bytes', data_get($j, 'memory_bytes', 0)),
                'disk_bytes' => (int) data_get($j, 'utilization.disk_bytes', data_get($j, 'disk_bytes', 0)),
                'source' => 'wings',
            ]);
        } catch (\Throwable $e) {
            return $base;
        }
    }
}
