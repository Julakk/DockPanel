<?php

namespace App\Http\Controllers;

use App\Models\Mount;
use App\Models\Node;
use Illuminate\Http\Request;

class MountController extends Controller
{
    public function index()
    {
        $mounts = Mount::withCount('nodes')->orderBy('name')->get();

        return view('mounts.index', compact('mounts'));
    }

    public function create()
    {
        $nodes = Node::orderBy('name')->get();

        return view('mounts.create', compact('nodes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'source' => 'required|string|max:255',
            'target' => 'required|string|max:255',
            'read_only' => 'boolean',
            'node_ids' => 'nullable|array',
            'node_ids.*' => 'exists:nodes,id',
        ]);

        $nodeIds = $validated['node_ids'] ?? [];
        unset($validated['node_ids']);

        $mount = Mount::create($validated);
        $mount->nodes()->sync($nodeIds);

        return redirect()->route('mounts.index')->with('success', "Mount '{$mount->name}' dibuat.");
    }

    public function edit(Mount $mount)
    {
        $nodes = Node::orderBy('name')->get();
        $mount->load('nodes');

        return view('mounts.edit', compact('mount', 'nodes'));
    }

    public function update(Request $request, Mount $mount)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'source' => 'required|string|max:255',
            'target' => 'required|string|max:255',
            'read_only' => 'boolean',
            'node_ids' => 'nullable|array',
            'node_ids.*' => 'exists:nodes,id',
        ]);

        $nodeIds = $validated['node_ids'] ?? [];
        unset($validated['node_ids']);

        $mount->update($validated);
        $mount->nodes()->sync($nodeIds);

        return redirect()->route('mounts.index')->with('success', "Mount '{$mount->name}' diupdate.");
    }

    public function destroy(Mount $mount)
    {
        $name = $mount->name;
        $mount->delete();

        return redirect()->route('mounts.index')->with('success', "Mount '{$name}' dihapus.");
    }
}
