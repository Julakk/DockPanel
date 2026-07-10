<?php

namespace App\Http\Controllers;

use App\Models\Node;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NodeController extends Controller
{
    public function index()
    {
        $nodes = Node::withCount('servers')->orderBy('name')->get();

        return view('nodes.index', compact('nodes'));
    }

    public function create()
    {
        return view('nodes.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateNode($request);

        // daemon_token cuma di-generate sekali pas node dibuat, ditampilin sekali doang
        $plainToken = Str::random(64);
        $validated['daemon_token'] = bcrypt($plainToken);

        $node = Node::create($validated);

        return redirect()
            ->route('nodes.show', $node)
            ->with('success', "Node '{$node->name}' dibuat. Token daemon: {$plainToken} (simpan sekarang, nggak bakal ditampilin lagi!)");
    }

    public function show(Node $node)
    {
        $node->loadCount('servers');

        return view('nodes.show', compact('node'));
    }

    public function edit(Node $node)
    {
        return view('nodes.edit', compact('node'));
    }

    public function update(Request $request, Node $node)
    {
        $validated = $this->validateNode($request, $node);

        $node->update($validated);

        return redirect()
            ->route('nodes.show', $node)
            ->with('success', "Node '{$node->name}' berhasil diupdate.");
    }

    public function destroy(Node $node)
    {
        if ($node->servers()->exists()) {
            return back()->withErrors([
                'delete' => 'Node ini masih punya server aktif, pindahin/hapus server-nya dulu.',
            ]);
        }

        $name = $node->name;
        $node->delete();

        return redirect()->route('nodes.index')->with('success', "Node '{$name}' dihapus.");
    }

    protected function validateNode(Request $request, ?Node $node = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'fqdn' => 'required|string|max:255',
            'scheme' => 'required|in:http,https',
            'public' => 'boolean',
            'behind_proxy' => 'boolean',
            'memory' => 'required|integer|min:0',
            'disk' => 'required|integer|min:0',
            'daemon_listen' => 'required|integer|min:1|max:65535',
            'daemon_sftp' => 'required|integer|min:1|max:65535',
        ]);
    }
}
