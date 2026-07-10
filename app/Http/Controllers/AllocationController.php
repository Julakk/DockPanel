<?php

namespace App\Http\Controllers;

use App\Models\Allocation;
use App\Models\Node;
use Illuminate\Http\Request;

class AllocationController extends Controller
{
    public function store(Request $request, Node $node)
    {
        $validated = $request->validate([
            'ip' => 'required|string|max:255',
            'port_start' => 'required|integer|min:1|max:65535',
            'port_end' => 'nullable|integer|min:1|max:65535|gte:port_start',
        ]);

        $start = $validated['port_start'];
        $end = $validated['port_end'] ?? $start;

        if ($end - $start > 100) {
            return back()->withErrors(['port_end' => 'Maksimal 100 port sekaligus biar nggak overload.']);
        }

        $created = 0;
        for ($port = $start; $port <= $end; $port++) {
            $exists = Allocation::where('node_id', $node->id)
                ->where('ip', $validated['ip'])
                ->where('port', $port)
                ->exists();

            if (! $exists) {
                Allocation::create([
                    'node_id' => $node->id,
                    'ip' => $validated['ip'],
                    'port' => $port,
                ]);
                $created++;
            }
        }

        return back()->with('success', "{$created} allocation ditambahkan.");
    }

    public function destroy(Node $node, Allocation $allocation)
    {
        if ($allocation->isAssigned()) {
            return back()->withErrors(['delete' => 'Allocation ini masih dipakai server, lepas dulu dari server-nya.']);
        }

        $allocation->delete();

        return back()->with('success', 'Allocation dihapus.');
    }
}
