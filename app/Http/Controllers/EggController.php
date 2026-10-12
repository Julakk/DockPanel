<?php

namespace App\Http\Controllers;

use App\Models\Egg;
use App\Models\EggVariable;
use App\Models\Nest;
use Illuminate\Http\Request;
use App\Services\EggImporter;
use InvalidArgumentException;

class EggController extends Controller
{
    public function index()
    {
        $eggs = Egg::with('nest')->withCount('servers')->orderBy('name')->get();

        return view('eggs.index', compact('eggs'));
    }

    public function create()
    {
        $nests = Nest::orderBy('name')->get();

        return view('eggs.create', compact('nests'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nest_id' => 'required|exists:nests,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'docker_image' => 'required|string|max:255',
            'startup' => 'required|string',
            'script_container' => 'nullable|string|max:255',
            'script_install' => 'nullable|string',
        ]);

        $egg = Egg::create($validated);

        return redirect()->route('eggs.edit', $egg)->with('success', "Egg '{$egg->name}' dibuat. Tambahin variable-nya sekarang.");
    }

    public function edit(Egg $egg)
    {
        $nests = Nest::orderBy('name')->get();
        $egg->load('variables');

        return view('eggs.edit', compact('egg', 'nests'));
    }

    public function update(Request $request, Egg $egg)
    {
        $validated = $request->validate([
            'nest_id' => 'required|exists:nests,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'docker_image' => 'required|string|max:255',
            'startup' => 'required|string',
            'script_container' => 'nullable|string|max:255',
            'script_install' => 'nullable|string',
        ]);

        $egg->update($validated);

        return redirect()->route('eggs.edit', $egg)->with('success', "Egg '{$egg->name}' diupdate.");
    }

    public function destroy(Egg $egg)
    {
        if ($egg->servers()->exists()) {
            return back()->withErrors(['delete' => 'Egg ini masih dipakai server, nggak bisa dihapus.']);
        }

        $name = $egg->name;
        $egg->delete();

        return redirect()->route('eggs.index')->with('success', "Egg '{$name}' dihapus.");
    }

    /**
     * Tambah variable baru ke egg (mis. SERVER_JARFILE, WORLD_NAME).
     */
    public function storeVariable(Request $request, Egg $egg)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'env_variable' => 'required|string|max:255|regex:/^[A-Z0-9_]+$/',
            'description' => 'nullable|string',
            'default_value' => 'nullable|string',
            'user_viewable' => 'boolean',
            'user_editable' => 'boolean',
            'rules' => 'required|string|max:255',
        ]);

        $egg->variables()->create($validated);

        return back()->with('success', 'Variable ditambahkan.');
    }

    public function destroyVariable(Egg $egg, EggVariable $variable)
    {
        $variable->delete();

        return back()->with('success', 'Variable dihapus.');
    }

    /**
     * Import egg dari format JSON kompatibel Pterodactyl.
     * Format: { "name", "description", "docker_image", "startup", "variables": [...] }
     */
    public function importForm()
    {
        $nests = Nest::orderBy('name')->get();

        return view('eggs.import', compact('nests'));
    }

    public function import(Request $request, EggImporter $importer)
    {
        $request->validate([
            'nest_id' => 'required|exists:nests,id',
            'egg_json' => 'required|file|max:512',
        ]);

        $data = json_decode(file_get_contents($request->file('egg_json')->getRealPath()), true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
            return back()->withErrors(['egg_json' => 'File JSON nggak valid: '.json_last_error_msg()]);
        }

        try {
            $egg = $importer->import($data, (int) $request->input('nest_id'));
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['egg_json' => $e->getMessage()]);
        }

        return redirect()->route('eggs.edit', $egg)->with('success', "Egg '{$egg->name}' berhasil diimport dari JSON.");
    }
}
