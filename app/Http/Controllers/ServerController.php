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
