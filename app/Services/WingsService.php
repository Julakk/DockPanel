<?php

namespace App\Services;

use App\Models\Server;
use App\Services\Concerns\WingsFiles;
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
    use WingsFiles;

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
     * Susun map ENV_VARIABLE => value buat dikirim ke Wings sebagai `docker -e`.
     * Ini yang bikin ${MAIN_FILE} dkk kebaca di container saat runtime, beda
     * dari renderStartup() yang cuma ganti placeholder {{VAR}} di teks command.
     */
    protected function envVariables(): array
    {
        $vars = [];

        foreach ($this->server->serverVariables()->with('eggVariable')->get() as $sv) {
            $vars[$sv->eggVariable->env_variable] = $sv->variable_value ?? $sv->eggVariable->default_value ?? '';
        }

        return $vars;
    }

    /**
     * Susun daftar allocation (ip+port) server ini buat dikirim ke Wings.
     * Yang primary ditandai 'primary' => true; port-nya dipakai buat
     * ngisi env SERVER_PORT/SERVER_IP di container.
     */
    protected function allocationsPayload(): array
    {
        return $this->server->allocations()->get()->map(fn ($a) => [
            'ip' => $a->ip,
            'port' => $a->port,
            'primary' => $a->is_primary,
        ])->values()->all();
    }

    public function createServer(): array
    {
        $response = $this->client()->post('/api/servers', [
            'uuid' => $this->server->uuid,
            'container' => [
                'image' => $this->server->image,
                'startup_command' => $this->server->egg->renderStartup($this->server),
                'env_variables' => $this->envVariables(),
            ],
            'build' => [
                'memory_limit' => $this->server->memory,
                'swap' => $this->server->swap,
                'io_weight' => $this->server->io,
                'cpu_limit' => $this->server->cpu,
                'disk_space' => $this->server->disk,
            ],
            'allocations' => $this->allocationsPayload(),
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
     *
     * PENTING: ditandatangani pakai daemon_token Node (SAMA dengan auth_token
     * di config.json Wings), BUKAN app.key. Wings cuma kenal daemon_token,
     * jadi secret verifikasinya harus itu.
     */
    public function generateWebsocketToken(): string
    {
        $payload = [
            'iss' => config('app.url'),
            'sub' => $this->server->uuid,
            'exp' => time() + 60, // token cuma valid 60 detik buat handshake awal
            'server_uuid' => $this->server->uuid,
        ];

        return JWT::encode($payload, $this->daemonToken, 'HS256');
    }

    public function daemonBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
