<?php

namespace App\Services;

use App\Models\Server;

/**
 * Samain kolom status Panel dengan state asli container dari Wings.
 * Cuma status "biasa" yang boleh ditimpa; installing, suspended,
 * restoring_backup, dan error sengaja nggak disentuh.
 */
class ServerStatusSync
{
    private const SYNC_FROM = ['running', 'offline', 'stopped', 'starting', 'stopping'];

    private const SYNC_TO = ['running', 'offline', 'starting'];

    /**
     * Cek hasil install ke Wings kalau status Panel masih "installing".
     * Balikannya status install dari Wings (running|completed|failed|idle) atau null.
     */
    public static function syncInstall(Server $server): ?string
    {
        if ((string) $server->status !== 'installing') {
            return null;
        }

        try {
            $server->loadMissing('node');
            if (! $server->node) {
                return null;
            }
            [$http, $res] = (new WingsService($server))->installStatus();
        } catch (\Throwable $e) {
            return null;
        }

        if ($http !== 200) {
            return null;
        }

        $state = (string) ($res['status'] ?? 'idle');
        $new = match ($state) {
            'completed' => 'offline',
            'failed', 'idle' => 'install_failed',
            default => null,
        };

        if ($new !== null) {
            try {
                $server->forceFill(['status' => $new])->saveQuietly();
            } catch (\Throwable $e) {
                // abaikan
            }
        }

        return $state;
    }

    public static function apply(Server $server, string $state): void
    {
        $current = (string) $server->status;

        if ($current === $state
            || ! in_array($state, self::SYNC_TO, true)
            || ! in_array($current, self::SYNC_FROM, true)) {
            return;
        }
        if ($current === 'stopped' && $state === 'offline') {
            return;
        }
        if ($current === 'stopping' && $state !== 'offline') {
            return;
        }

        try {
            $server->forceFill(['status' => $state])->saveQuietly();
        } catch (\Throwable $e) {
            // Gagal nyimpen nggak boleh ganggu resource bar.
        }
    }
}
