<?php

namespace App\Services;

use App\Models\Node;
use App\Models\Server;

/**
 * Satu pintu buat provision ke Wings + catat state retry.
 * Percobaan gagal dijadwalin ulang pakai backoff; command
 * servers:retry-provision yang ngeksekusi tiap menit.
 */
class ProvisionService
{
    public const MAX_ATTEMPTS = 5;

    /** Menit jeda setelah percobaan ke-1, 2, 3, ... */
    public const BACKOFF = [1, 2, 5, 15, 30];

    /** @return array{0:bool,1:?string} [berhasil, pesan error] */
    public function attempt(Server $server): array
    {
        $server->loadMissing('node');

        try {
            $res = (new WingsService($server))->createServer();

            if (! empty($res['error'])) {
                throw new \RuntimeException((string) $res['error']);
            }

            $allowed = ['running', 'stopped', 'offline'];
            $status = $res['status'] ?? 'offline';

            $server->forceFill([
                'status' => in_array($status, $allowed, true) ? $status : 'offline',
                'provision_attempts' => 0,
                'provision_next_at' => null,
                'provision_last_error' => null,
            ])->save();

            return [true, null];
        } catch (\Throwable $e) {
            $msg = Node::explainWingsError($e->getMessage(), (string) $server->node?->scheme);
            $this->recordFailure($server, $msg);

            return [false, $msg];
        }
    }

    public function recordFailure(Server $server, string $message): void
    {
        $attempts = (int) $server->provision_attempts + 1;
        $next = $attempts < self::MAX_ATTEMPTS
            ? now()->addMinutes(self::BACKOFF[$attempts - 1] ?? 30)
            : null;

        $server->forceFill([
            'provision_attempts' => $attempts,
            'provision_next_at' => $next,
            'provision_last_error' => mb_substr($message, 0, 1000),
        ])->save();
    }
}
