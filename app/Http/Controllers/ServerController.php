<?php

namespace App\Http\Controllers;

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
    public function index()
    {
        $servers = Server::with(['owner', 'node', 'egg'])->orderBy('name')->get();

        return view('servers.index', compact('servers'));
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

        // Siapin baris server_variables kosong buat tiap egg_variable, biar tinggal diisi di halaman edit
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
        $server->load(['owner', 'node', 'egg.nest', 'serverVariables.eggVariable', 'allocations', 'databases.databaseHost', 'mounts']);

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

    /**
     * Update semua nilai variable server sekaligus (batch, dari form edit).
     */
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

    /**
     * Sync mount yang di-assign ke server ini (dari checklist di halaman edit).
     */
    public function updateMounts(Request $request, Server $server)
    {
        $validated = $request->validate([
            'mount_ids' => 'nullable|array',
            'mount_ids.*' => 'exists:mounts,id',
        ]);

        $server->mounts()->sync($validated['mount_ids'] ?? []);

        return back()->with('success', 'Mount server diupdate.');
    }

    /**
     * Coba provision server ke Wings di node yang dipilih.
     * Karena belum ada VPS/Wings asli buat dites, ini bakal gagal
     * dengan graceful error sampai node beneran tersedia.
     */
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

    public function destroy(Server $server)
    {
        $name = $server->name;
        $server->delete();

        return redirect()->route('servers.index')->with('success', "Server '{$name}' dihapus.");
    }
}
