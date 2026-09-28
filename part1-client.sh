#!/usr/bin/env bash
# DockPanel - PART 1: Client area (user) ala Pterodactyl
set -e
[ -f artisan ] || { echo "Jalankan dari root repo DockPanel (yang ada file artisan)"; exit 1; }
mkdir -p resources/views/client

cat > routes/client.php << 'EOF'
<?php

use App\Http\Controllers\ClientServerController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('client/servers/{server}')->name('client.servers.')->group(function () {
    Route::get('/', [ClientServerController::class, 'show'])->name('show');
    Route::get('resources', [ClientServerController::class, 'resources'])->name('resources');
    Route::post('power', [ClientServerController::class, 'power'])->name('power');
    Route::post('command', [ClientServerController::class, 'command'])->name('command');
    Route::put('rename', [ClientServerController::class, 'rename'])->name('rename');
    Route::put('startup', [ClientServerController::class, 'updateStartup'])->name('startup.update');
    Route::post('users', [ClientServerController::class, 'addUser'])->name('users.store');
    Route::delete('users/{user}', [ClientServerController::class, 'removeUser'])->name('users.destroy');
});
EOF

cat > app/Http/Controllers/ClientServerController.php << 'EOF'
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
EOF

cat > resources/views/client/server.blade.php << 'EOF'
@extends('layouts.client')

@section('title', $server->name)

@section('content')
<style>
.dp-wrap{max-width:960px;margin:0 auto}
.dp-head{display:flex;flex-wrap:wrap;gap:.75rem;justify-content:space-between;align-items:center;margin-bottom:1rem}
.dp-card{background:var(--card,rgba(127,127,127,.08));border:1px solid var(--border,rgba(127,127,127,.25));border-radius:10px;padding:1rem;margin-bottom:1rem}
.dp-tabs{display:flex;gap:.25rem;flex-wrap:wrap;margin-bottom:1rem;overflow-x:auto}
.dp-tabs a{padding:.45rem .9rem;border-radius:8px;text-decoration:none;border:1px solid var(--border,rgba(127,127,127,.25));color:inherit;font-size:.9rem;white-space:nowrap}
.dp-tabs a.active{background:var(--primary,#3b82f6);color:#fff;border-color:transparent}
.dp-power{display:flex;gap:.4rem;flex-wrap:wrap}
.dp-power button,.dp-btn{padding:.45rem .9rem;border-radius:8px;border:1px solid var(--border,rgba(127,127,127,.35));background:transparent;color:inherit;cursor:pointer}
.dp-power button:disabled{opacity:.4;cursor:not-allowed}
.dp-power .p-start{border-color:var(--green)!important;color:var(--green)!important}
.dp-power .p-restart{border-color:var(--amber)!important;color:var(--amber)!important}
.dp-power .p-stop,.dp-power .p-kill{border-color:var(--red)!important;color:var(--red)!important}
.dp-res{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.75rem}
.dp-bar{height:8px;border-radius:99px;background:rgba(127,127,127,.25);overflow:hidden;margin:.35rem 0}
.dp-bar>span{display:block;height:100%;width:0;background:var(--primary,#3b82f6);transition:width .4s}
.dp-muted{opacity:.65;font-size:.85rem}
.dp-alert{padding:.6rem .9rem;border-radius:8px;margin-bottom:1rem}
.dp-ok{background:rgba(34,197,94,.15)}.dp-err{background:rgba(239,68,68,.15)}
.dp-console{background:#0b0d0f;color:#cbd5e1;font-family:monospace;font-size:.85rem;padding:1rem;border-radius:8px;min-height:180px;white-space:pre-wrap}
.dp-input{width:100%;padding:.5rem .7rem;border-radius:8px;border:1px solid var(--border,rgba(127,127,127,.35));background:transparent;color:inherit;box-sizing:border-box;margin-bottom:0}
.dp-table td{padding:.3rem .6rem .3rem 0;vertical-align:top}
.dp-field{margin-bottom:.9rem}
.dp-perms{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.3rem;margin:.5rem 0}
.dp-perms label{display:flex;align-items:center;gap:.4rem;margin:0}
.dp-perms input{width:auto;margin:0}
</style>

<div class="dp-wrap">
    @if (session('success'))<div class="dp-alert dp-ok">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="dp-alert dp-err">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="dp-alert dp-err">{{ $errors->first() }}</div>@endif

    <div class="dp-head">
        <div>
            <h1 style="margin:0">{{ $server->name }}</h1>
            <div class="dp-muted">
                {{ $server->uuid_short }}
                @if ($server->primaryAllocation)
                    · {{ $server->primaryAllocation->ip }}:{{ $server->primaryAllocation->port }}
                @endif
                @if ($server->suspended) · <strong>SUSPENDED</strong> @endif
            </div>
        </div>
        <form method="POST" action="{{ route('client.servers.power', $server) }}" class="dp-power">
            @csrf
            @foreach ([['start','Start',$canStart],['restart','Restart',$canRestart],['stop','Stop',$canStop],['kill','Kill',$canStop]] as [$act,$label,$allowed])
                <button type="submit" name="action" value="{{ $act }}" class="p-{{ $act }}" @disabled($server->suspended || ! $allowed)>{{ $label }}</button>
            @endforeach
        </form>
    </div>

    <div class="dp-card">
        <div class="dp-res">
            <div><div>CPU <span class="dp-muted" id="dp-cpu-t">-</span></div><div class="dp-bar"><span id="dp-cpu"></span></div></div>
            <div><div>Memory <span class="dp-muted" id="dp-mem-t">-</span></div><div class="dp-bar"><span id="dp-mem"></span></div></div>
            <div><div>Disk <span class="dp-muted" id="dp-disk-t">-</span></div><div class="dp-bar"><span id="dp-disk"></span></div></div>
        </div>
        <div class="dp-muted" id="dp-state">Status: memuat...</div>
    </div>

    @if ($server->expires_at)
        <div class="dp-card">
            Masa aktif sampai <strong>{{ $server->expires_at->format('d M Y H:i') }}</strong>
            <span class="dp-muted">({{ $server->expires_at->diffForHumans() }})</span>
            @if ($server->suspended && $server->suspension_reason === 'expired')
                — <strong>disuspend karena expired</strong>
            @endif
        </div>
    @endif

    @if (auth()->user()->root_admin)
        <form method="POST" action="{{ route('servers.expiry.update', $server) }}" class="dp-card" style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:end">
            @csrf
            @method('PUT')
            <div>
                <div class="dp-muted">Set tanggal expired (admin)</div>
                <input class="dp-input" type="datetime-local" name="expires_at"
                       value="{{ $server->expires_at?->format('Y-m-d\TH:i') }}">
            </div>
            <div>
                <div class="dp-muted">atau perpanjang (hari)</div>
                <input class="dp-input" type="number" name="extend_days" min="1" max="3650" placeholder="30" style="width:110px">
            </div>
            <button class="dp-btn" type="submit">Simpan</button>
        </form>
    @endif

    <div class="dp-tabs">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('client.servers.show', ['server' => $server, 'tab' => $key]) }}" class="{{ $tab === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if ($tab === 'console')
        <div class="dp-card">
            <div class="dp-console">Console real-time belum aktif.
Butuh koneksi WebSocket ke Wings (roadmap: WebSocket console).
Sementara ini kamu bisa kirim command lewat form di bawah.</div>
            <form method="POST" action="{{ route('client.servers.command', $server) }}" style="margin-top:.75rem;display:flex;gap:.5rem">
                @csrf
                <input class="dp-input" type="text" name="command" maxlength="255" placeholder="Ketik command, mis. say halo" required @disabled($server->suspended)>
                <button class="dp-btn" type="submit" @disabled($server->suspended)>Kirim</button>
            </form>
        </div>

    @elseif ($tab === 'files')
        <div class="dp-card">
            <strong>File Manager</strong>
            <p class="dp-muted">Belum tersedia. Menunggu proxy SFTP ke Wings (butuh VPS buat testing).</p>
            <p class="dp-muted" style="margin-bottom:0">Sementara, akses file lewat SFTP: host <code>{{ $server->node->fqdn ?? '-' }}</code>, port <code>{{ $server->node->daemon_sftp ?? '-' }}</code>.</p>
        </div>

    @elseif ($tab === 'databases')
        <div class="dp-card">
            <strong>Databases</strong>
            @forelse ($server->databases as $db)
                <div style="border-top:1px solid rgba(127,127,127,.25);margin-top:.6rem;padding-top:.6rem">
                    <table class="dp-table">
                        <tr><td class="dp-muted">Host</td><td><code>{{ $db->databaseHost->host ?? '-' }}:{{ $db->databaseHost->port ?? '' }}</code></td></tr>
                        <tr><td class="dp-muted">Database</td><td><code>{{ $db->database }}</code></td></tr>
                        <tr><td class="dp-muted">Username</td><td><code>{{ $db->username }}</code></td></tr>
                        <tr><td class="dp-muted">Password</td><td><details><summary style="cursor:pointer">Tampilkan</summary><code>{{ $db->password }}</code></details></td></tr>
                    </table>
                </div>
            @empty
                <p class="dp-muted" style="margin-bottom:0">Server ini belum punya database. Minta admin buat provision.</p>
            @endforelse
        </div>

    @elseif ($tab === 'schedules')
        @include('client.partials.schedules')

    @elseif ($tab === 'users')
        <div class="dp-card">
            <strong>Users dengan akses ke server ini</strong>
            <table style="margin-top:.5rem">
                <thead><tr><th>User</th><th>Peran</th><th></th></tr></thead>
                <tbody>
                    <tr><td>{{ $server->owner->name }}<div class="dp-muted">{{ $server->owner->email }}</div></td><td>Owner</td><td></td></tr>
                    @foreach ($server->subusers as $sub)
                        @php($perms = json_decode($sub->pivot->permissions ?? '[]', true) ?: [])
                        <tr>
                            <td>{{ $sub->name }}<div class="dp-muted">{{ $sub->email }}</div></td>
                            <td class="dp-muted">{{ count($perms) }} permission</td>
                            <td>
                                <form method="POST" action="{{ route('client.servers.users.destroy', [$server, $sub]) }}" data-confirm="Cabut akses {{ $sub->name }}?">
                                    @csrf @method('DELETE')
                                    <button class="dp-btn" type="submit">Cabut</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <form method="POST" action="{{ route('client.servers.users.store', $server) }}" class="dp-card">
            @csrf
            <strong>Tambah subuser</strong>
            <div class="dp-field" style="margin-top:.5rem">
                <div class="dp-muted">Email (harus sudah punya akun)</div>
                <input class="dp-input" type="email" name="email" required>
            </div>
            <div class="dp-muted">Permission</div>
            <div class="dp-perms">
                @foreach ($availablePermissions as $key => $label)
                    <label><input type="checkbox" name="permissions[]" value="{{ $key }}"> {{ $label }}</label>
                @endforeach
            </div>
            <button class="dp-btn" type="submit">Tambah</button>
        </form>

    @elseif ($tab === 'network')
        <div class="dp-card">
            <strong>Allocations</strong>
            <table style="margin-top:.5rem">
                <thead><tr><th>IP</th><th>Port</th><th></th></tr></thead>
                <tbody>
                    @forelse ($server->allocations as $a)
                        <tr>
                            <td>{{ $a->ip_alias ?: $a->ip }}</td>
                            <td>{{ $a->port }}</td>
                            <td>@if ($a->is_primary)<span class="status-badge status-active">Primary</span>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="dp-muted">Belum ada allocation.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    @elseif ($tab === 'startup')
        <div class="dp-card">
            <div class="dp-muted">Startup command</div>
            <pre class="dp-console" style="min-height:0">{{ $server->startup ?: '-' }}</pre>
            <div class="dp-muted">Docker image</div>
            <pre class="dp-console" style="min-height:0;margin-bottom:0">{{ $server->image ?: '-' }}</pre>
        </div>
        @php($visible = $server->serverVariables->filter(fn ($sv) => $sv->eggVariable && ($isAdmin || $sv->eggVariable->user_viewable)))
        @if ($visible->isNotEmpty())
            <form method="POST" action="{{ route('client.servers.startup.update', $server) }}" class="dp-card">
                @csrf @method('PUT')
                <strong>Variables</strong>
                @foreach ($visible as $sv)
                    @php($ev = $sv->eggVariable)
                    @php($editable = $isManager && ($isAdmin || $ev->user_editable))
                    <div class="dp-field" style="margin-top:.8rem">
                        <div>{{ $ev->name }} <code>{{ $ev->env_variable }}</code></div>
                        @if ($ev->description)<div class="dp-muted">{{ $ev->description }}</div>@endif
                        <input class="dp-input" type="text" name="variables[{{ $ev->id }}]" value="{{ $sv->variable_value }}" @disabled(! $editable)>
                    </div>
                @endforeach
                @if ($isManager)<button class="dp-btn" type="submit">Simpan variable</button>@endif
            </form>
        @endif

    @elseif ($tab === 'settings')
        <div class="dp-card">
            <table class="dp-table">
                <tr><td class="dp-muted">UUID</td><td>{{ $server->uuid }}</td></tr>
                <tr><td class="dp-muted">Node</td><td>{{ $server->node->name ?? '-' }}</td></tr>
                <tr><td class="dp-muted">Egg</td><td>{{ $server->egg->name ?? '-' }}</td></tr>
                <tr><td class="dp-muted">Memory</td><td>{{ $server->memory ? $server->memory.' MB' : 'Unlimited' }}</td></tr>
                <tr><td class="dp-muted">Disk</td><td>{{ $server->disk ? $server->disk.' MB' : 'Unlimited' }}</td></tr>
                <tr><td class="dp-muted">CPU</td><td>{{ $server->cpu ? $server->cpu.'%' : 'Unlimited' }}</td></tr>
                <tr><td class="dp-muted">SFTP</td><td><code>{{ $server->node->fqdn ?? '-' }}:{{ $server->node->daemon_sftp ?? '-' }}</code></td></tr>
            </table>
        </div>
        @if ($isManager)
            <form method="POST" action="{{ route('client.servers.rename', $server) }}" class="dp-card">
                @csrf @method('PUT')
                <strong>Detail server</strong>
                <div class="dp-field" style="margin-top:.6rem">
                    <div class="dp-muted">Nama</div>
                    <input class="dp-input" type="text" name="name" value="{{ old('name', $server->name) }}" maxlength="255" required>
                </div>
                <div class="dp-field">
                    <div class="dp-muted">Deskripsi</div>
                    <input class="dp-input" type="text" name="description" value="{{ old('description', $server->description) }}" maxlength="500">
                </div>
                <button class="dp-btn" type="submit">Simpan</button>
            </form>
        @endif

    @elseif ($tab === 'activity')
        <div class="dp-card">
            <strong>Aktivitas server</strong>
            <table style="margin-top:.5rem">
                <thead><tr><th>Event</th><th>User</th><th>Waktu</th></tr></thead>
                <tbody>
                    @forelse ($activities as $a)
                        <tr>
                            <td><code>{{ $a->event }}</code></td>
                            <td>{{ $a->user->name ?? '-' }}</td>
                            <td class="dp-muted">{{ $a->created_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="dp-muted">Belum ada aktivitas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>

<script>
(function () {
    const url = @json(route('client.servers.resources', $server));
    const mb = b => b >= 1073741824 ? (b / 1073741824).toFixed(2) + ' GB' : (b / 1048576).toFixed(0) + ' MB';
    const pct = (v, l) => l > 0 ? Math.min(100, (v / l) * 100) : 0;
    const set = (id, p, t) => {
        document.getElementById('dp-' + id).style.width = p + '%';
        document.getElementById('dp-' + id + '-t').textContent = t;
    };
    async function tick() {
        try {
            const r = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            if (!r.ok) return;
            const d = await r.json();
            set('cpu', Math.min(100, d.cpu_percent), d.cpu_percent + '%');
            set('mem', pct(d.memory_bytes, d.memory_limit_bytes),
                mb(d.memory_bytes) + ' / ' + (d.memory_limit_bytes ? mb(d.memory_limit_bytes) : '∞'));
            set('disk', pct(d.disk_bytes, d.disk_limit_bytes),
                mb(d.disk_bytes) + ' / ' + (d.disk_limit_bytes ? mb(d.disk_limit_bytes) : '∞'));
            document.getElementById('dp-state').textContent =
                'Status: ' + d.state + (d.source === 'mock' ? ' (Wings belum terhubung)' : '');
        } catch (e) {}
    }
    tick();
    setInterval(tick, 5000);
})();
</script>
@endsection
EOF

cat > resources/views/client/servers.blade.php << 'EOF'
@extends('layouts.client')

@section('title', 'My Servers - DockPanel')

@section('content')
    <h2 style="margin-top:0;">Halo, {{ $user->name }} @include('partials.icon', ['name' => 'sparkle', 'size' => 22])</h2>
    <p class="muted" style="margin-top:-0.6rem;">Ini daftar server yang kamu punya akses.</p>

    @if ($servers->isEmpty())
        <div class="card">
            <div class="empty-state">
                <div class="icon">@include('partials.icon', ['name' => 'package', 'size' => 40])</div>
                <p>Kamu belum punya server. Hubungi admin buat dibuatin server baru.</p>
            </div>
        </div>
    @else
        <input type="search" id="dp-server-filter" placeholder="Cari server..." style="max-width:320px">

        @foreach ($servers as $server)
            <a href="{{ route('client.servers.show', $server) }}" class="server-card status-{{ $server->status }}-border" data-server-card data-name="{{ strtolower($server->name) }}" data-url="{{ route('client.servers.resources', $server) }}" style="text-decoration:none;color:inherit">
                <div class="server-card-icon">
                    @include('partials.icon', ['name' => 'package', 'size' => 18])
                </div>

                <div>
                    <div class="server-card-name">{{ $server->name }}</div>
                    <div class="server-card-sub">{{ $server->node->name }} / {{ $server->egg->name }}@if ($server->expires_at) · exp {{ $server->expires_at->format('d M Y') }}@endif</div>
                </div>

                <span class="status-badge status-{{ $server->suspended ? 'suspended' : $server->status }}" data-state style="margin-left:0.5rem;">{{ $server->suspended ? 'suspended' : $server->status }}</span>

                <div class="server-card-stats">
                    <div class="stat">
                        <div class="stat-label">CPU</div>
                        <div class="stat-bar"><div class="stat-bar-fill" data-bar="cpu" style="width:0%;"></div></div>
                        <div class="stat-value" data-val="cpu">—</div>
                    </div>
                    <div class="stat">
                        <div class="stat-label">Memory</div>
                        <div class="stat-bar"><div class="stat-bar-fill" data-bar="mem" style="width:0%;"></div></div>
                        <div class="stat-value" data-val="mem">—</div>
                    </div>
                    <div class="stat">
                        <div class="stat-label">Disk</div>
                        <div class="stat-bar"><div class="stat-bar-fill" data-bar="disk" style="width:0%;"></div></div>
                        <div class="stat-value" data-val="disk">—</div>
                    </div>
                </div>
            </a>
        @endforeach

        <script>
        (function () {
            const cards = [...document.querySelectorAll('[data-server-card]')];
            const mb = b => b >= 1073741824 ? (b / 1073741824).toFixed(1) + 'G' : (b / 1048576).toFixed(0) + 'M';
            const pct = (v, l) => l > 0 ? Math.min(100, (v / l) * 100) : 0;
            const put = (c, k, p, t) => {
                c.querySelector('[data-bar="' + k + '"]').style.width = p + '%';
                c.querySelector('[data-val="' + k + '"]').textContent = t;
            };
            async function refresh(c) {
                try {
                    const r = await fetch(c.dataset.url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                    if (!r.ok) return;
                    const d = await r.json();
                    if (d.source === 'mock') return;
                    put(c, 'cpu', Math.min(100, d.cpu_percent), d.cpu_percent + '%');
                    put(c, 'mem', pct(d.memory_bytes, d.memory_limit_bytes), mb(d.memory_bytes) + (d.memory_limit_bytes ? ' / ' + mb(d.memory_limit_bytes) : ''));
                    put(c, 'disk', pct(d.disk_bytes, d.disk_limit_bytes), mb(d.disk_bytes) + (d.disk_limit_bytes ? ' / ' + mb(d.disk_limit_bytes) : ''));
                    const s = c.querySelector('[data-state]');
                    if (!s.textContent.includes('suspended')) {
                        s.textContent = d.state;
                        s.className = 'status-badge status-' + (d.state === 'running' ? 'running' : d.state === 'offline' ? 'offline' : 'starting');
                    }
                } catch (e) {}
            }
            cards.forEach(refresh);
            setInterval(() => cards.forEach(refresh), 10000);

            document.getElementById('dp-server-filter').addEventListener('input', e => {
                const q = e.target.value.trim().toLowerCase();
                cards.forEach(c => { c.style.display = c.dataset.name.includes(q) ? '' : 'none'; });
            });
        })();
        </script>
    @endif
@endsection
EOF

echo "PART 1 selesai."
