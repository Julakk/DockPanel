<?php

namespace App\Http\Controllers;

use App\Models\DatabaseHost;
use App\Models\Node;
use Illuminate\Http\Request;

class DatabaseHostController extends Controller
{
    public function index()
    {
        $hosts = DatabaseHost::with('node')->orderBy('name')->get();

        return view('databases.index', compact('hosts'));
    }

    public function create()
    {
        $nodes = Node::orderBy('name')->get();

        return view('databases.create', compact('nodes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'password' => 'required|string',
            'node_id' => 'nullable|exists:nodes,id',
        ]);

        $host = DatabaseHost::create($validated);

        return redirect()->route('databases.index')->with('success', "Database host '{$host->name}' dibuat.");
    }

    public function edit(DatabaseHost $database)
    {
        $nodes = Node::orderBy('name')->get();

        return view('databases.edit', ['host' => $database, 'nodes' => $nodes]);
    }

    public function update(Request $request, DatabaseHost $database)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'password' => 'nullable|string',
            'node_id' => 'nullable|exists:nodes,id',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $database->update($validated);

        return redirect()->route('databases.index')->with('success', "Database host '{$database->name}' diupdate.");
    }

    public function destroy(DatabaseHost $database)
    {
        $name = $database->name;
        $database->delete();

        return redirect()->route('databases.index')->with('success', "Database host '{$name}' dihapus.");
    }
}
