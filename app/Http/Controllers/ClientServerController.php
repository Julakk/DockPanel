<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Services\ServerResourceService;
use App\Services\WingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientServerController extends Controller
{
    private const TABS = ['console', 'files', 'settings', 'startup', 'schedules'];

    /**
     * Boleh akses: root admin, owner, atau subuser server ini.
     */
    private function authorizeAccess(Request $request, Server $server): void
    {
        $user = $request->user();

        $isAdmin = (bool) ($user->root_admin ?? false);
        $isOwner = (int) $server->owner_id === (int) $user->id;
        $isSubuser = $server->subusers()->where('users.id', $user->id)->exists();

        abort_unless($isAdmin || $isOwner || $isSubuser, 403);
    }

    public function show(Request $request, Server $server)
    {
        $this->authorizeAccess($request, $server);

        $tab = $request->query('tab', 'console');
        if (! in_array($tab, self::TABS, true)) {
            $tab = 'console';
        }

        $server->load(['node', 'egg', 'primaryAllocation']);

        return view('client.server', compact('server', 'tab'));
    }

    public function resources(Request $request, Server $server, ServerResourceService $resources): JsonResponse
    {
        $this->authorizeAccess($request, $server);

        return response()->json($resources->for($server));
    }

    public function power(Request $request, Server $server)
    {
        $this->authorizeAccess($request, $server);

        $data = $request->validate([
            'action' => ['required', 'in:start,stop,restart,kill'],
        ]);

        if ($server->suspended) {
            return back()->with('error', 'Server lagi di-suspend, power action dimatiin.');
        }

        try {
            $ok = (new WingsService($server->loadMissing('node')))->power($data['action']);
        } catch (\Throwable $e) {
            $ok = false;
        }

        return $ok
            ? back()->with('success', "Power action '{$data['action']}' dikirim ke Wings.")
            : back()->with('error', 'Gagal hubungi Wings. Node belum aktif atau nggak bisa dijangkau.');
    }

    public function command(Request $request, Server $server)
    {
        $this->authorizeAccess($request, $server);

        $data = $request->validate([
            'command' => ['required', 'string', 'max:255'],
        ]);

        if ($server->suspended) {
            return back()->with('error', 'Server lagi di-suspend.');
        }

        try {
            $ok = (new WingsService($server->loadMissing('node')))->sendCommand($data['command']);
        } catch (\Throwable $e) {
            $ok = false;
        }

        return $ok
            ? back()->with('success', 'Command terkirim.')
            : back()->with('error', 'Gagal kirim command. Wings belum aktif?');
    }
}
