#!/usr/bin/env bash
# DockPanel - PART 2: Admin panel ala Pterodactyl
set -e
[ -f artisan ] || { echo "Jalankan dari root repo DockPanel (yang ada file artisan)"; exit 1; }
mkdir -p resources/views/nodes resources/views/servers

cat > app/Http/Controllers/DashboardController.php << 'EOF'
<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Egg;
use App\Models\Node;
use App\Models\Server;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->isRootAdmin()) {
            return view('dashboard', [
                'user' => $user,
                'nodeCount' => Node::count(),
                'serverCount' => Server::count(),
                'eggCount' => Egg::count(),
                'userCount' => User::count(),
                'runningCount' => Server::where('status', 'running')->where('suspended', false)->count(),
                'installingCount' => Server::where('status', 'installing')->count(),
                'suspendedCount' => Server::where('suspended', true)->count(),
                'expiringCount' => Server::whereNotNull('expires_at')
                    ->where('expires_at', '<=', now()->addDays(7))->count(),
                'nodes' => Node::withCount('servers')->orderBy('name')->get(),
                'recentServers' => Server::with(['owner', 'node'])->latest()->limit(5)->get(),
                'recentActivity' => ActivityLog::with(['user', 'server'])->latest()->limit(8)->get(),
            ]);
        }

        $servers = Server::with(['node', 'egg'])
            ->where('owner_id', $user->id)
            ->orWhereHas('subusers', fn ($q) => $q->where('users.id', $user->id))
            ->orderBy('name')
            ->get();

        return view('client.servers', compact('user', 'servers'));
    }
}
EOF

cat > resources/views/dashboard.blade.php << 'EOF'
@extends('layouts.app')

@section('title', 'Overview')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>Overview
@endsection

@section('content')
    <h2 style="margin-top:0;">Administrative Overview</h2>
    <p class="muted" style="margin-top:-0.6rem;">A quick glance at your system, {{ $user->name }}. DockPanel <code>v{{ config('app.version') }}</code></p>

    <div class="row">
        @foreach ([
            ['Nodes', $nodeCount], ['Servers', $serverCount], ['Users', $userCount], ['Eggs', $eggCount],
        ] as [$label, $value])
            <div class="card" style="flex:1; min-width:130px; text-align:center;">
                <div class="muted" style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em;">{{ $label }}</div>
                <div style="font-size:1.8rem; font-weight:700; margin-top:0.3rem;">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="card" style="flex:1; min-width:130px; text-align:center;">
            <div class="muted" style="font-size:0.75rem;text-transform:uppercase;">Running</div>
            <div style="font-size:1.4rem;font-weight:700;color:var(--green)">{{ $runningCount }}</div>
        </div>
        <div class="card" style="flex:1; min-width:130px; text-align:center;">
            <div class="muted" style="font-size:0.75rem;text-transform:uppercase;">Installing</div>
            <div style="font-size:1.4rem;font-weight:700;color:var(--amber)">{{ $installingCount }}</div>
        </div>
        <div class="card" style="flex:1; min-width:130px; text-align:center;">
            <div class="muted" style="font-size:0.75rem;text-transform:uppercase;">Suspended</div>
            <div style="font-size:1.4rem;font-weight:700;color:var(--gray)">{{ $suspendedCount }}</div>
        </div>
        <div class="card" style="flex:1; min-width:130px; text-align:center;">
            <div class="muted" style="font-size:0.75rem;text-transform:uppercase;">Expired / &lt;7 hari</div>
            <div style="font-size:1.4rem;font-weight:700;color:var(--red)">{{ $expiringCount }}</div>
        </div>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Nodes</h3>
        @if ($nodes->isEmpty())
            <p class="muted" style="margin:0;">Belum ada node. <a href="{{ route('nodes.create') }}">Buat node pertama</a>.</p>
        @else
            <table>
                <thead><tr><th>Node</th><th>Server</th><th>Memory</th><th>Disk</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach ($nodes as $n)
                        @php($mp = $n->memory > 0 ? min(100, $n->memoryUsed() / $n->memory * 100) : 0)
                        @php($dp = $n->disk > 0 ? min(100, $n->diskUsed() / $n->disk * 100) : 0)
                        <tr>
                            <td><a href="{{ route('nodes.show', $n) }}">{{ $n->name }}</a><div class="muted">{{ $n->fqdn }}</div></td>
                            <td>{{ $n->servers_count }}</td>
                            <td style="min-width:110px"><div class="stat-bar"><div class="stat-bar-fill" style="width:{{ $mp }}%"></div></div><div class="stat-value">{{ $n->memoryUsed() }} / {{ number_format($n->memory) }} MB</div></td>
                            <td style="min-width:110px"><div class="stat-bar"><div class="stat-bar-fill" style="width:{{ $dp }}%"></div></div><div class="stat-value">{{ $n->diskUsed() }} / {{ number_format($n->disk) }} MB</div></td>
                            <td>@if ($n->maintenance_mode)<span class="status-badge status-maintenance">Maintenance</span>@else<span class="status-badge status-active">Aktif</span>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="row">
        <div class="card" style="flex:1; min-width:280px;">
            <h3 style="margin-top:0;">Server terbaru</h3>
            @forelse ($recentServers as $s)
                <div style="display:flex;justify-content:space-between;gap:.5rem;padding:.4rem 0;border-bottom:1px solid var(--border)">
                    <a href="{{ route('servers.show', $s) }}">{{ $s->name }}</a>
                    <span class="muted">{{ $s->owner->name ?? '-' }} · {{ $s->node->name ?? '-' }}</span>
                </div>
            @empty
                <p class="muted" style="margin:0;">Belum ada server.</p>
            @endforelse
        </div>
        <div class="card" style="flex:1; min-width:280px;">
            <h3 style="margin-top:0;">Aktivitas terbaru</h3>
            @forelse ($recentActivity as $a)
                <div style="display:flex;justify-content:space-between;gap:.5rem;padding:.4rem 0;border-bottom:1px solid var(--border)">
                    <span><code>{{ $a->event }}</code> <span class="muted">{{ $a->user->name ?? '' }}</span></span>
                    <span class="muted">{{ $a->created_at?->diffForHumans() }}</span>
                </div>
            @empty
                <p class="muted" style="margin:0;">Belum ada aktivitas.</p>
            @endforelse
        </div>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Quick Links</h3>
        <div style="display:flex; gap:0.6rem; flex-wrap:wrap;">
            <a href="{{ route('nodes.index') }}" class="btn btn-primary">@include('partials.icon', ['name' => 'server', 'size' => 16]) Kelola Nodes</a>
            <a href="{{ route('servers.index') }}" class="btn btn-secondary">@include('partials.icon', ['name' => 'package', 'size' => 16]) Kelola Servers</a>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">@include('partials.icon', ['name' => 'users', 'size' => 16]) Kelola Users</a>
            <a href="{{ route('eggs.index') }}" class="btn btn-secondary">@include('partials.icon', ['name' => 'egg', 'size' => 16]) Kelola Eggs</a>
        </div>
    </div>
@endsection
EOF

cat > app/Http/Controllers/ServerController.php << 'EOF'
<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Allocation;
use App\Models\DatabaseHost;
use App\Models\Egg;
use App\Models\Mount;
use App\Models\Node;
use App\Models\Server;
use App\Models\User;
use App\Services\WingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServerController extends Controller
{
    public function index(Request $request)
    {
        $query = Server::with(['owner', 'node', 'egg']);

        if ($q = trim((string) $request->query('q', ''))) {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('uuid_short', 'like', "%{$q}%")
                    ->orWhereHas('owner', fn ($o) => $o->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"));
            });
        }

        if ($status = $request->query('status')) {
            if ($status === 'suspended') {
                $query->where('suspended', true);
            } else {
                $query->where('status', $status);
            }
        }

        if ($nodeId = $request->query('node')) {
            $query->where('node_id', $nodeId);
        }

        $servers = $query->orderBy('name')->paginate(15)->withQueryString();
        $nodes = Node::orderBy('name')->get();

        return view('servers.index', compact('servers', 'nodes'));
    }

    public function create()
    {
        $users = User::orderBy('name')->get();
        $nodes = Node::orderBy('name')->get();
        $eggs = Egg::with('nest')->orderBy('name')->get();
        $allocations = Allocation::with('node')->whereNull('server_id')->orderBy('ip')->get();

        return view('servers.create', compact('users', 'nodes', 'eggs', 'allocations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'owner_id' => 'required|exists:users,id',
            'node_id' => 'required|exists:nodes,id',
            'egg_id' => 'required|exists:eggs,id',
            'allocation_id' => 'nullable|exists:allocations,id',
            'memory' => 'required|integer|min:0',
            'swap' => 'required|integer|min:0',
            'disk' => 'required|integer|min:0',
            'io' => 'required|integer|min:10|max:1000',
            'cpu' => 'required|numeric|min:0',
        ]);

        $egg = Egg::findOrFail($validated['egg_id']);
        $allocationId = $validated['allocation_id'] ?? null;
        unset($validated['allocation_id']);

        $server = Server::create([
            ...$validated,
            'nest_id' => $egg->nest_id,
            'image' => $egg->docker_image,
            'startup' => $egg->startup,
            'status' => 'installing',
        ]);

        if ($allocationId) {
            Allocation::where('id', $allocationId)
                ->whereNull('server_id')
                ->update(['server_id' => $server->id, 'is_primary' => true]);
        }

        foreach ($egg->variables as $eggVariable) {
            $server->serverVariables()->create([
                'egg_variable_id' => $eggVariable->id,
                'variable_value' => $eggVariable->default_value,
            ]);
        }

        return redirect()
            ->route('servers.edit', $server)
            ->with('success', "Server '{$server->name}' dibuat. Isi variable-nya, terus provision ke node.");
    }

    public function show(Server $server)
    {
        $server->load(['owner', 'node', 'egg.nest', 'serverVariables.eggVariable', 'allocations', 'databases.databaseHost', 'mounts', 'subusers']);

        return view('servers.show', compact('server'));
    }

    public function edit(Server $server)
    {
        $server->load(['owner', 'node', 'egg', 'serverVariables.eggVariable', 'databases.databaseHost', 'mounts', 'subusers']);
        $users = User::orderBy('name')->get();
        $databaseHosts = DatabaseHost::orderBy('name')->get();
        $allMounts = Mount::orderBy('name')->get();
        $availablePermissions = ServerSubuserController::AVAILABLE_PERMISSIONS;

        return view('servers.edit', compact('server', 'users', 'databaseHosts', 'allMounts', 'availablePermissions'));
    }

    public function update(Request $request, Server $server)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'owner_id' => 'required|exists:users,id',
            'memory' => 'required|integer|min:0',
            'swap' => 'required|integer|min:0',
            'disk' => 'required|integer|min:0',
            'io' => 'required|integer|min:10|max:1000',
            'cpu' => 'required|numeric|min:0',
        ]);

        $server->update($validated);

        return redirect()->route('servers.edit', $server)->with('success', "Server '{$server->name}' diupdate.");
    }

    public function updateVariables(Request $request, Server $server)
    {
        $values = $request->input('variables', []);

        DB::transaction(function () use ($server, $values) {
            foreach ($values as $eggVariableId => $value) {
                $server->serverVariables()
                    ->where('egg_variable_id', $eggVariableId)
                    ->update(['variable_value' => $value]);
            }
        });

        return back()->with('success', 'Variable server diupdate.');
    }

    public function updateMounts(Request $request, Server $server)
    {
        $validated = $request->validate([
            'mount_ids' => 'nullable|array',
            'mount_ids.*' => 'exists:mounts,id',
        ]);

        $server->mounts()->sync($validated['mount_ids'] ?? []);

        return back()->with('success', 'Mount server diupdate.');
    }

    public function provision(Server $server)
    {
        try {
            $wings = new WingsService($server);
            $wings->createServer();

            $server->update(['status' => 'running']);

            return back()->with('success', 'Server berhasil di-provision ke Wings.');
        } catch (\Throwable $e) {
            return back()->withErrors([
                'provision' => 'Gagal provision ke Wings: '.$e->getMessage().' (wajar kalau node belum aktif/VPS belum ada)',
            ]);
        }
    }

    public function suspend(Server $server)
    {
        $server->update(['suspended' => true, 'suspension_reason' => 'admin']);

        try {
            (new WingsService($server->loadMissing('node')))->power('stop');
        } catch (\Throwable $e) {
            // Wings belum aktif, flag suspended tetap kepasang
        }

        ActivityLog::record('server:suspend', [], $server);

        return back()->with('success', "Server '{$server->name}' di-suspend.");
    }

    public function unsuspend(Server $server)
    {
        $server->update(['suspended' => false, 'suspension_reason' => null]);
        ActivityLog::record('server:unsuspend', [], $server);

        return back()->with('success', "Server '{$server->name}' diaktifkan lagi.");
    }

    public function destroy(Server $server)
    {
        $name = $server->name;
        $server->delete();

        return redirect()->route('servers.index')->with('success', "Server '{$name}' dihapus.");
    }
}
EOF

cat > resources/views/servers/index.blade.php << 'EOF'
@extends('layouts.app')

@section('title', 'Servers - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>Servers
@endsection

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; gap:.5rem; flex-wrap:wrap;">
        <h2 style="margin:0;">Servers <span class="muted">({{ $servers->total() }})</span></h2>
        <a href="{{ route('servers.create') }}" class="btn btn-primary">+ Buat Server</a>
    </div>

    <form method="GET" action="{{ route('servers.index') }}" class="card" style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:end;padding:1rem;">
        <div style="flex:2;min-width:180px"><label>Cari (nama / ID / owner)</label><input type="search" name="q" value="{{ request('q') }}" style="margin:0"></div>
        <div style="flex:1;min-width:130px"><label>Status</label>
            <select name="status" style="margin:0">
                <option value="">Semua</option>
                @foreach (['running','installing','offline','suspended','install_failed'] as $st)
                    <option value="{{ $st }}" @selected(request('status') === $st)>{{ $st }}</option>
                @endforeach
            </select>
        </div>
        <div style="flex:1;min-width:130px"><label>Node</label>
            <select name="node" style="margin:0">
                <option value="">Semua</option>
                @foreach ($nodes as $n)
                    <option value="{{ $n->id }}" @selected((string) request('node') === (string) $n->id)>{{ $n->name }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-primary" type="submit">Filter</button>
        @if (request()->hasAny(['q','status','node']))<a href="{{ route('servers.index') }}" class="btn btn-secondary">Reset</a>@endif
    </form>

    @if ($servers->isEmpty())
        <div class="card">
            <div class="empty-state">
                <div class="icon">@include('partials.icon', ['name' => 'package', 'size' => 40])</div>
                <p>Tidak ada server yang cocok. Pastikan udah ada Node dan Egg sebelum bikin server.</p>
                <a href="{{ route('servers.create') }}" class="btn btn-primary">+ Buat Server</a>
            </div>
        </div>
    @else
        @foreach ($servers as $server)
            <div class="server-card status-{{ $server->status }}-border">
                <div class="server-card-icon">
                    @include('partials.icon', ['name' => 'package', 'size' => 18])
                </div>

                <div>
                    <div class="server-card-name">
                        <a href="{{ route('servers.show', $server) }}">{{ $server->name }}</a>
                        <span class="muted">#{{ $server->uuid_short }}</span>
                    </div>
                    <div class="server-card-sub">{{ $server->owner->name }} — {{ $server->node->name }} / {{ $server->egg->name }}</div>
                </div>

                <span class="status-badge status-{{ $server->suspended ? 'suspended' : $server->status }}" style="margin-left:0.5rem;">{{ $server->suspended ? 'suspended' : $server->status }}</span>

                <div class="server-card-stats">
                    <div class="stat"><div class="stat-label">Memory</div><div class="stat-value">{{ $server->memory ? $server->memory.' MB' : '∞' }}</div></div>
                    <div class="stat"><div class="stat-label">Disk</div><div class="stat-value">{{ $server->disk ? $server->disk.' MB' : '∞' }}</div></div>
                    <div class="stat"><div class="stat-label">CPU</div><div class="stat-value">{{ $server->cpu ? $server->cpu.'%' : '∞' }}</div></div>
                </div>

                <a href="{{ route('servers.edit', $server) }}" class="btn btn-secondary" style="margin-left:0.5rem;">Edit</a>
            </div>
        @endforeach

        @if ($servers->hasPages())
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:1rem;">
                @if ($servers->onFirstPage())<span class="muted">&larr; Sebelumnya</span>@else<a class="btn btn-secondary" href="{{ $servers->previousPageUrl() }}">&larr; Sebelumnya</a>@endif
                <span class="muted">Halaman {{ $servers->currentPage() }} / {{ $servers->lastPage() }}</span>
                @if ($servers->hasMorePages())<a class="btn btn-secondary" href="{{ $servers->nextPageUrl() }}">Berikutnya &rarr;</a>@else<span class="muted">Berikutnya &rarr;</span>@endif
            </div>
        @endif
    @endif
@endsection
EOF

cat > resources/views/servers/show.blade.php << 'EOF'
@extends('layouts.app')

@section('title', $server->name . ' - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>
    <a href="{{ route('servers.index') }}">Servers</a><span class="sep">&gt;</span>{{ $server->name }}
@endsection

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; gap:.5rem; flex-wrap:wrap;">
        <h2 style="margin:0;">{{ $server->name }} <span class="status-badge status-{{ $server->suspended ? 'suspended' : $server->status }}">{{ $server->suspended ? 'suspended' : $server->status }}</span></h2>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
            <a href="{{ route('client.servers.show', $server) }}" class="btn btn-secondary">Buka sebagai user</a>
            <a href="{{ route('servers.edit', $server) }}" class="btn btn-primary">Edit (Details, Build, Startup, DB, Mounts)</a>
        </div>
    </div>

    @if ($errors->any())<div class="error">{{ $errors->first() }}</div>@endif

    <div class="row">
        <div class="card" style="flex:1;min-width:280px;">
            <h3 style="margin-top:0;">About</h3>
            <table>
                <tr><th>ID</th><td><code>{{ $server->uuid_short }}</code></td></tr>
                <tr><th>UUID</th><td class="muted">{{ $server->uuid }}</td></tr>
                <tr><th>Owner</th><td>{{ $server->owner->name }} <span class="muted">{{ $server->owner->email }}</span></td></tr>
                <tr><th>Node</th><td><a href="{{ route('nodes.show', $server->node) }}">{{ $server->node->name }}</a></td></tr>
                <tr><th>Nest / Egg</th><td>{{ $server->egg->nest->name }} / {{ $server->egg->name }}</td></tr>
                <tr><th>Docker Image</th><td class="muted">{{ $server->image }}</td></tr>
                @if ($server->expires_at)<tr><th>Expired</th><td>{{ $server->expires_at->format('d M Y H:i') }} <span class="muted">({{ $server->expires_at->diffForHumans() }})</span></td></tr>@endif
            </table>
        </div>
        <div class="card" style="flex:1;min-width:280px;">
            <h3 style="margin-top:0;">Build Configuration</h3>
            <table>
                <tr><th>Memory</th><td>{{ $server->memory ? number_format($server->memory).' MB' : 'Unlimited' }}</td></tr>
                <tr><th>Swap</th><td>{{ $server->swap }} MB</td></tr>
                <tr><th>Disk</th><td>{{ $server->disk ? number_format($server->disk).' MB' : 'Unlimited' }}</td></tr>
                <tr><th>CPU</th><td>{{ $server->cpu ? $server->cpu.'%' : 'Unlimited' }}</td></tr>
                <tr><th>Block IO</th><td>{{ $server->io }}</td></tr>
            </table>
        </div>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Allocations</h3>
        <table>
            <thead><tr><th>IP</th><th>Port</th><th></th></tr></thead>
            <tbody>
                @forelse ($server->allocations as $alloc)
                    <tr><td>{{ $alloc->ip }}</td><td>{{ $alloc->port }}</td><td>@if ($alloc->is_primary)<span class="status-badge status-active">Primary</span>@endif</td></tr>
                @empty
                    <tr><td colspan="3" class="muted">Belum ada allocation.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="row">
        <div class="card" style="flex:1;min-width:280px;">
            <h3 style="margin-top:0;">Startup</h3>
            <div class="muted">Command</div>
            <code style="display:block;white-space:pre-wrap;margin-bottom:.8rem;">{{ $server->startup }}</code>
            @foreach ($server->serverVariables as $sv)
                <div style="display:flex;justify-content:space-between;gap:.5rem;padding:.3rem 0;border-bottom:1px solid var(--border)">
                    <span>{{ $sv->eggVariable->name ?? '-' }} <code>{{ $sv->eggVariable->env_variable ?? '' }}</code></span>
                    <span class="muted">{{ $sv->variable_value }}</span>
                </div>
            @endforeach
        </div>
        <div class="card" style="flex:1;min-width:280px;">
            <h3 style="margin-top:0;">Databases, Mounts &amp; Subusers</h3>
            <div class="muted">Databases</div>
            @forelse ($server->databases as $db)<div><code>{{ $db->database }}</code> <span class="muted">@ {{ $db->databaseHost->name ?? '-' }}</span></div>@empty<div class="muted">-</div>@endforelse
            <div class="muted" style="margin-top:.6rem;">Mounts</div>
            @forelse ($server->mounts as $m)<div>{{ $m->name }}</div>@empty<div class="muted">-</div>@endforelse
            <div class="muted" style="margin-top:.6rem;">Subusers</div>
            @forelse ($server->subusers as $u)<div>{{ $u->name }} <span class="muted">{{ $u->email }}</span></div>@empty<div class="muted">-</div>@endforelse
        </div>
    </div>

    <div class="card" style="border-top-color:var(--red)!important;">
        <h3 style="margin-top:0;">Manage</h3>
        <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
            <form method="POST" action="{{ route('servers.provision', $server) }}" data-confirm="Provision server ke Wings sekarang?">
                @csrf <button class="btn btn-secondary" type="submit">Provision ke Wings</button>
            </form>
            @if ($server->suspended)
                <form method="POST" action="{{ route('servers.unsuspend', $server) }}">
                    @csrf <button class="btn btn-primary" type="submit">Unsuspend</button>
                </form>
            @else
                <form method="POST" action="{{ route('servers.suspend', $server) }}" data-confirm="Suspend server '{{ $server->name }}'? Server akan dihentikan.">
                    @csrf <button class="btn btn-secondary" type="submit">Suspend</button>
                </form>
            @endif
            <form method="POST" action="{{ route('servers.destroy', $server) }}" data-confirm="Hapus server '{{ $server->name }}' permanen?">
                @csrf @method('DELETE') <button class="btn btn-danger" type="submit">Hapus Server</button>
            </form>
        </div>
    </div>
@endsection
EOF

cat > app/Http/Controllers/NodeConfigController.php << 'EOF'
<?php

namespace App\Http\Controllers;

use App\Models\Node;

class NodeConfigController extends Controller
{
    public function show(Node $node)
    {
        $config = [
            'listen_addr' => ':'.$node->daemon_listen,
            'sftp_addr' => ':'.$node->daemon_sftp,
            'auth_token' => $node->daemon_token,
            'docker_socket' => '/var/run/docker.sock',
            'data_directory' => '/var/lib/dockwings/servers',
        ];

        return view('nodes.configuration', [
            'node' => $node,
            'json' => json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ]);
    }
}
EOF

cat > resources/views/nodes/configuration.blade.php << 'EOF'
@extends('layouts.app')

@section('title', $node->name . ' - Configuration')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>
    <a href="{{ route('nodes.index') }}">Nodes</a><span class="sep">&gt;</span>
    <a href="{{ route('nodes.show', $node) }}">{{ $node->name }}</a><span class="sep">&gt;</span>Configuration
@endsection

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
        <h2 style="margin:0;">Configuration — {{ $node->name }}</h2>
        <a href="{{ route('nodes.show', $node) }}" class="btn btn-secondary">&larr; Kembali</a>
    </div>

    <div class="card">
        <p class="muted" style="margin-top:0;">
            Simpan sebagai <code>/etc/dockwings/config.json</code> di VPS node, lalu restart
            <code>systemctl restart dockwings</code>. Isinya termasuk token rahasia — jangan dibagikan.
        </p>
        <pre id="dp-node-config" style="background:var(--bg);border:1px solid var(--border);padding:1rem;border-radius:var(--radius);overflow-x:auto;margin:0 0 1rem;">{{ $json }}</pre>
        <button type="button" class="btn btn-primary" onclick="navigator.clipboard.writeText(document.getElementById('dp-node-config').textContent).then(()=>this.textContent='Tersalin ✓')">Salin config</button>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Install cepat di node</h3>
        <pre style="background:var(--bg);border:1px solid var(--border);padding:1rem;border-radius:var(--radius);overflow-x:auto;margin:0;">bash &lt;(curl -s https://raw.githubusercontent.com/Julakk/DockPanel/main/install.sh)</pre>
        <p class="muted" style="margin-bottom:0;">Pilih opsi 2 (Node/Wings), lalu tempel token di atas saat diminta.</p>
    </div>
@endsection
EOF

cat > routes/admin_extra.php << 'EOF'
<?php

use App\Http\Controllers\NodeConfigController;
use App\Http\Controllers\ServerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'root_admin'])->group(function () {
    Route::post('servers/{server}/suspend', [ServerController::class, 'suspend'])->name('servers.suspend');
    Route::post('servers/{server}/unsuspend', [ServerController::class, 'unsuspend'])->name('servers.unsuspend');
    Route::get('nodes/{node}/configuration', [NodeConfigController::class, 'show'])->name('nodes.config');
});
EOF

grep -q "admin_extra.php" routes/web.php || echo "require __DIR__.'/admin_extra.php';" >> routes/web.php

if ! grep -q "nodes.config" resources/views/nodes/show.blade.php; then
  sed -i 's|<a href="{{ route(.nodes.edit., $node) }}" class="btn btn-secondary">Edit</a>|<div style="display:flex;gap:.5rem;"><a href="{{ route("nodes.config", $node) }}" class="btn btn-secondary">Configuration</a><a href="{{ route("nodes.edit", $node) }}" class="btn btn-secondary">Edit</a></div>|' resources/views/nodes/show.blade.php
  grep -q "nodes.config" resources/views/nodes/show.blade.php || echo "PERINGATAN: link Configuration belum kepasang di nodes/show, buka langsung /nodes/{id}/configuration"
fi

echo "PART 2 selesai."
