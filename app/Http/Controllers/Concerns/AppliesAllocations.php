<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Node;
use App\Models\Server;
use App\Services\WingsService;

/**
 * Terapkan perubahan allocation (port) ke container lewat Wings.
 * Panel menyimpan perubahan lebih dulu; kalau Wings gagal, user dikasih tahu
 * bahwa perubahannya baru tersimpan di Panel.
 */
trait AppliesAllocations
{
    /**
     * Kirim allocation terbaru ke Wings, lalu balikin [kunci flash, pesan]
     * dengan $base di depan. Kunci flash: 'success' atau 'error'.
     *
     * @return array{0:string,1:string}
     */
    protected function allocationFlash(Server $server, string $base): array
    {
        $server->loadMissing('node');

        if (! $server->node) {
            return ['success', $base];
        }

        try {
            [$status, $data] = (new WingsService($server))->pushAllocations();
        } catch (\Throwable $e) {
            $why = Node::explainWingsError($e->getMessage(), (string) $server->node->scheme);

            return ['error', "{$base} Tersimpan di Panel, tapi Wings nggak bisa dihubungi: {$why}"];
        }

        $error = $data['error'] ?? null;

        if ($status === 200) {
            $note = ($data['restarted'] ?? false)
                ? 'Container dibuat ulang dan server direstart.'
                : 'Perubahan port diterapkan ke container.';

            return ['success', "{$base} {$note}"];
        }

        // Server belum pernah di-provision: port baru dipakai pas Provision.
        if ($status === 404 && str_contains((string) $error, 'daemon ini')) {
            return ['success', $base];
        }

        if ($status === 404 || $status === 405) {
            return ['error', "{$base} Tersimpan di Panel, tapi Wings belum mendukung perubahan port (butuh DockWings v0.4.1+)."];
        }

        if ($status === 401) {
            return ['error', "{$base} Tersimpan di Panel, tapi token node ditolak Wings."];
        }

        $why = $error ?: "HTTP {$status}";

        return ['error', "{$base} Tersimpan di Panel, tapi Wings menolak: {$why}"];
    }
}
