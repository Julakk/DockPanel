<?php

namespace App\Services;

use App\Models\Server;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;

/**
 * WingsService — jembatan Panel ke daemon Wings di tiap node.
 *
 * Semua request ke Wings pakai:
 *  1. HTTP Bearer token = "Bearer {node.daemon_token}" (identifikasi node)
 *  2. Untuk operasi spesifik server, kirim JWT signed pendek (short-lived)
 *     yang isinya server_uuid + permission, biar Wings tau request ini
 *     valid dan scope-nya cuma buat server itu.
 *
 * NOTE: Ini skeleton pemanggilan API. Implementasi endpoint Wings asli
 * (/api/servers, /api/servers/{uuid}/power, dst) mengikuti protokol
 * Wings resmi — cek dokumentasi Wings buat detail path & payload persis.
 */
class WingsService
{
    protected string $baseUrl;

    protected string $daemonToken;

    public function __construct(protected Server $server)
    {
        $node = $server->node;
        $this->baseUrl = $node->daemonBaseUrl();
        $this->daemonToken = $node->daemon_token;
    }

    protected function client()
    {
        return Http::withToken($this->daemonToken)
            ->baseUrl($this->baseUrl)
            ->acceptJson();
    }

    /**
     * Suruh Wings bikin & start container buat server ini.
     * Dipanggil sekali pas server pertama kali dibuat (fase "installing").
     */
    public function createServer(): array
    {
        $response = $this->client()->post('/api/servers', [
            'uuid' => $this->server->uuid,
            'container' => [
                'image' => $this->server->image,
                'startup_command' => $this->server->egg->renderStartup($this->server),
            ],
            'build' => [
                'memory_limit' => $this->server->memory,
                'swap' => $this->server->swap,
                'io_weight' => $this->server->io,
                'cpu_limit' => $this->server->cpu,
                'disk_space' => $this->server->disk,
            ],
        ]);

        return $response->json() ?? [];
    }

    /**
     * Kirim power action: start | stop | restart | kill
     */
    public function power(string $action): bool
    {
        $response = $this->client()->post("/api/servers/{$this->server->uuid}/power", [
            'action' => $action,
        ]);

        return $response->successful();
    }

    /**
     * Kirim command ke console server yang lagi jalan (mis. "say halo" di Minecraft).
     */
    public function sendCommand(string $command): bool
    {
        $response = $this->client()->post("/api/servers/{$this->server->uuid}/commands", [
            'commands' => [$command],
        ]);

        return $response->successful();
    }

    public function delete(): bool
    {
        return $this->client()->delete("/api/servers/{$this->server->uuid}")->successful();
    }

    /**
     * Generate JWT short-lived buat otorisasi koneksi WebSocket console
     * dari browser user langsung ke Wings (bukan lewat Panel, biar console
     * real-time nggak numpuk di Laravel queue).
     */
    public function generateWebsocketToken(): string
    {
        $payload = [
            'iss' => config('app.url'),
            'sub' => $this->server->uuid,
            'exp' => time() + 60, // token cuma valid 60 detik buat handshake awal
            'server_uuid' => $this->server->uuid,
        ];

        return JWT::encode($payload, config('app.key'), 'HS256');
    }
}
