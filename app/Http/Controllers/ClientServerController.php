<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Server;
use App\Models\User;
use App\Services\ServerResourceService;
use App\Services\WingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ClientServerController extends Controller
{
    /** key tab => [label, permission subuser yang dibutuhkan (null = semua yang punya akses)] */
    private const TABS = [
        'console' => ['Console', 'console.access'],
        'files' => ['Files', 'files.read'],
        'databases' => ['Databases', 'database.view'],
        'schedules' => ['Schedules', null],
        'users' => ['Users', 'manage'],
        'network' => ['Network', null],
        'startup' => ['Startup', null],
        'settings' => ['Settings', null],
        'activity' => ['Activity', null],
    ];

    private function isManager(Request $request, Server $server): bool
    {
        $user = $request->user();

        return (bool) ($user->root_admin ?? false) || (int) $server->owner_id === (int) $user->id;
    }

    /**
     * null = akses penuh (admin/owner). Array = daftar permission subuser.
     * Bukan admin/owner/subuser => 403.
     */
    private function permissions(Request $request, Server $server): ?array
    {
        if ($this->isManager($request, $server)) {
            return null;
        }

        $sub = $server->subusers()->where('users.id', $request->user()->id)->first();
        abort_unless($sub, 403);

        $perms = json_decode($sub->pivot->permissions ?? '[]', true);

        return is_array($perms) ? $perms : [];
    }

    private function can(Request $request, Server $server, ?string $perm): bool
    {
        if ($perm === null) {
            $this->permissions($request, $server);

            return true;
        }

        if ($perm === 'manage') {
            return $this->isManager($request, $server);
        }

        $perms = $this->permissions($request, $server);

        return $perms === null || in_array($perm, $perms, true);
    }

    private function authorizeAccess(Request $request, Server $server): void
    {
        $this->permissions($request, $server);
    }

    private function requireManager(Request $request, Server $server): void
    {
        abort_unless($this->isManager($request, $server), 403);
    }

    public function show(Request $request, Server $server)
    {
        $this->authorizeAccess($request, $server);

        $tabs = [];
        foreach (self::TABS as $key => [$label, $perm]) {
            if ($this->can($request, $server, $perm)) {
                $tabs[$key] = $label;
            }
        }

        $tab = $request->query('tab', 'console');
        if (! isset($tabs[$tab])) {
            $tab = array_key_first($tabs);
        }

        $server->load(['node', 'egg', 'primaryAllocation', 'allocations']);

        $data = [];
        if ($tab === 'databases') {
            $server->load('databases.databaseHost');
        } elseif ($tab === 'users') {
            $server->load(['owner', 'subusers']);
            $data['availablePermissions'] = ServerSubuserController::AVAILABLE_PERMISSIONS;
        } elseif ($tab === 'startup') {
            $server->load('serverVariables.eggVariable');
            $data['isAdmin'] = (bool) ($request->user()->root_admin ?? false);
        } elseif ($tab === 'activity') {
            $data['activities'] = ActivityLog::with('user')
                ->where('server_id', $server->id)
                ->latest()->limit(30)->get();
        }

        return view('client.server', array_merge($data, [
            'server' => $server,
            'tab' => $tab,
            'tabs' => $tabs,
            'isManager' => $this->isManager($request, $server),
            'canStart' => $this->can($request, $server, 'control.start'),
            'canStop' => $this->can($request, $server, 'control.stop'),
            'canRestart' => $this->can($request, $server, 'control.restart'),
        ]));
    }

    public function resources(Request $request, Server $server, ServerResourceService $resources): JsonResponse
    {
        $this->authorizeAccess($request, $server);

        return response()->json($resources->for($server));
    }

    public function power(Request $request, Server $server)
    {
        $data = $request->validate([
            'action' => ['required', 'in:start,stop,restart,kill'],
        ]);

        $perm = match ($data['action']) {
            'start' => 'control.start',
            'restart' => 'control.restart',
            default => 'control.stop',
        };
        abort_unless($this->can($request, $server, $perm), 403);

        if ($server->suspended) {
            return back()->with('error', 'Server lagi di-suspend, power action dimatiin.');
        }

        try {
            $ok = (new WingsService($server->loadMissing('node')))->power($data['action']);
        } catch (\Throwable $e) {
            $ok = false;
        }

        ActivityLog::record('server:power', ['action' => $data['action'], 'ok' => $ok], $server);

        return $ok
            ? back()->with('success', "Power action '{$data['action']}' dikirim ke Wings.")
            : back()->with('error', 'Gagal hubungi Wings. Node belum aktif atau nggak bisa dijangkau.');
    }

    public function command(Request $request, Server $server)
    {
        abort_unless($this->can($request, $server, 'console.access'), 403);

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

    public function rename(Request $request, Server $server)
    {
        $this->requireManager($request, $server);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $server->update($data);
        ActivityLog::record('server:rename', ['name' => $data['name']], $server);

        return redirect()
            ->route('client.servers.show', ['server' => $server, 'tab' => 'settings'])
            ->with('success', 'Detail server diupdate.');
    }

    public function updateStartup(Request $request, Server $server)
    {
        $this->requireManager($request, $server);

        $isAdmin = (bool) ($request->user()->root_admin ?? false);
        $server->load('serverVariables.eggVariable');
        $errors = [];

        foreach ($server->serverVariables as $sv) {
            $ev = $sv->eggVariable;
            if (! $ev || (! $isAdmin && ! $ev->user_editable)) {
                continue;
            }
            if (! $request->has("variables.{$ev->id}")) {
                continue;
            }

            $value = (string) $request->input("variables.{$ev->id}", '');
            $rules = $ev->rules ?: 'nullable|string|max:255';

            try {
                $v = Validator::make([$ev->env_variable => $value], [$ev->env_variable => $rules]);
                $failed = $v->fails();
                $msg = $failed ? $v->errors()->first() : null;
            } catch (\Throwable $e) {
                $failed = mb_strlen($value) > 255;
                $msg = 'Nilai terlalu panjang.';
            }

            if ($failed) {
                $errors[] = "{$ev->name}: {$msg}";

                continue;
            }

            $sv->update(['variable_value' => $value]);
        }

        if ($errors) {
            return back()->with('error', implode(' | ', $errors));
        }

        ActivityLog::record('server:startup', [], $server);

        return back()->with('success', 'Variable startup diupdate.');
    }

    public function addUser(Request $request, Server $server)
    {
        $this->requireManager($request, $server);

        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'permissions' => 'nullable|array',
            'permissions.*' => Rule::in(array_keys(ServerSubuserController::AVAILABLE_PERMISSIONS)),
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ((int) $user->id === (int) $server->owner_id) {
            return back()->with('error', 'User ini udah jadi owner server.');
        }
        if ($server->subusers()->where('users.id', $user->id)->exists()) {
            return back()->with('error', 'User ini udah jadi subuser di server ini.');
        }

        $server->subusers()->attach($user->id, [
            'permissions' => json_encode($validated['permissions'] ?? []),
        ]);
        ActivityLog::record('server:subuser.add', ['email' => $user->email], $server);

        return redirect()
            ->route('client.servers.show', ['server' => $server, 'tab' => 'users'])
            ->with('success', "{$user->name} ditambahin sebagai subuser.");
    }

    public function removeUser(Request $request, Server $server, User $user)
    {
        $this->requireManager($request, $server);

        $server->subusers()->detach($user->id);
        ActivityLog::record('server:subuser.remove', ['email' => $user->email], $server);

        return redirect()
            ->route('client.servers.show', ['server' => $server, 'tab' => 'users'])
            ->with('success', "{$user->name} dicabut dari server ini.");
    }
}
