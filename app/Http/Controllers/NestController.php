<?php

namespace App\Http\Controllers;

use App\Models\Nest;
use Illuminate\Http\Request;

class NestController extends Controller
{
    public function index()
    {
        $nests = Nest::withCount('eggs')->orderBy('name')->get();

        return view('nests.index', compact('nests'));
    }

    public function create()
    {
        return view('nests.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $nest = Nest::create($validated);

        return redirect()->route('nests.index')->with('success', "Nest '{$nest->name}' dibuat.");
    }

    public function edit(Nest $nest)
    {
        return view('nests.edit', compact('nest'));
    }

    public function update(Request $request, Nest $nest)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $nest->update($validated);

        return redirect()->route('nests.index')->with('success', "Nest '{$nest->name}' diupdate.");
    }

    public function destroy(Nest $nest)
    {
        if ($nest->eggs()->exists()) {
            return back()->withErrors(['delete' => 'Nest ini masih punya egg, hapus egg-nya dulu.']);
        }

        $name = $nest->name;
        $nest->delete();

        return redirect()->route('nests.index')->with('success', "Nest '{$name}' dihapus.");
    }
}
