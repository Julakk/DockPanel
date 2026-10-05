<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ActivityLog;
use App\Models\Node;
use App\Models\Server;
use App\Services\ServerStatusSync;
use App\Services\WingsService;
use Illuminate\Http\Request;

/**
 * Reinstall server (script install egg lewat Wings) dan pengaturan status install.
 */
trait ManagesInstall
{
    public function reinstall(Request $request, Server $server)
    {
        $request->validate(['confirm' => ['accepted']]);

        $server->loadMissing(['node', 'egg']);

        if (in_array($server->status, ['installing', 'restoring_backup'], true)) {
            return $this->installError('Server lagi '.$server->status.', tunggu selesai dulu.');
        }
        if (! $server->node) {
            return $this->installError('Server ini belum punya node.');
        }
        if (trim((string) $server->egg?->script_install) === '') {
            return $this->installError('Egg server ini belum punya script install.');
        }

        try {
            [$status, $res] = (new WingsService($server))->startInstall();
        } catch (\Throwable $e) {
            return $this->installError('Wings nggak bisa dihubungi: '.Node::explainWingsError($e->getMessage(), (string) $server->node->scheme));
        }

        if ($status !== 202) {
            return $this->installError($this->installErrorMessage($status, $res));
        }

        $server->update(['status' => 'installing']);
        ActivityLog::record('server:reinstall', ['name' => $server->name], $server);

        return back()->with('success', 'Reinstall dimulai di background. Klik "Cek dari Wings" buat lihat hasilnya.');
    }

    public function syncInstall(Server $server)
    {
        $state = ServerStatusSync::syncInstall($server);
        $server->refresh();

        $message = match ($state) {
            'running' => 'Install masih berjalan.',
            'completed' => 'Install selesai. Status server: '.$server->status.'.',
            'failed', 'idle' => 'Install gagal atau terputus. Status server: '.$server->status.'.',
            default => 'Status server: '.$server->status.'.',
        };

        return back()->with('success', $message);
    }

    public function setInstallStatus(Request $request, Server $server)
    {
        $data = $request->validate(['status' => ['required', 'in:offline,install_failed']]);

        $server->update(['status' => $data['status']]);
        ActivityLog::record('server:install-status', ['status' => $data['status']], $server);

        return back()->with('success', 'Status install diubah ke '.$data['status'].'.');
    }

    protected function installError(string $message)
    {
        return back()->withErrors(['install' => $message]);
    }

    protected function installErrorMessage(int $status, array $res): string
    {
        $error = $res['error'] ?? null;

        if ($status === 404 && ! $error) {
            return 'Wings di node ini belum punya endpoint install. Update DockWings dulu.';
        }
        if ($status === 401 || $status === 403) {
            return 'Token node ditolak Wings. Cek daemon_token di node.';
        }

        return $error ?: "Wings balikin HTTP {$status}.";
    }
}
