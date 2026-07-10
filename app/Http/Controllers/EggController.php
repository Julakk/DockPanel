<?php

namespace App\Http\Controllers;

use App\Models\Egg;
use App\Models\EggVariable;
use App\Models\Nest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function import(Request $request)
    {
        $request->validate([
            'nest_id' => 'required|exists:nests,id',
            'egg_json' => 'required|file',
        ]);

        $content = file_get_contents($request->file('egg_json')->getRealPath());
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors(['egg_json' => 'File JSON nggak valid: '.json_last_error_msg()]);
        }

        if (empty($data['name']) || empty($data['startup'])) {
            return back()->withErrors(['egg_json' => 'JSON harus punya field "name" dan "startup" minimal.']);
        }

        $egg = DB::transaction(function () use ($data, $request) {
            $egg = Egg::create([
                'nest_id' => $request->input('nest_id'),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'docker_image' => $data['docker_image'] ?? ($data['docker_images'][0] ?? 'alpine:3.4'),
                'docker_images' => $data['docker_images'] ?? null,
                'startup' => $data['startup'],
                'config_files' => $data['config_files'] ?? null,
                'config_startup' => $data['config']['startup'] ?? null,
                'config_stop' => $data['config']['stop'] ?? null,
                'script_container' => $data['scripts']['installation']['container'] ?? 'alpine:3.4',
                'script_install' => $data['scripts']['installation']['script'] ?? null,
            ]);

            foreach ($data['variables'] ?? [] as $var) {
                $egg->variables()->create([
                    'name' => $var['name'] ?? $var['env_variable'],
                    'env_variable' => $var['env_variable'],
                    'description' => $var['description'] ?? null,
                    'default_value' => $var['default_value'] ?? null,
                    'user_viewable' => $var['user_viewable'] ?? true,
                    'user_editable' => $var['user_editable'] ?? true,
                    'rules' => $var['rules'] ?? 'nullable|string',
                ]);
            }

            return $egg;
        });

        return redirect()->route('eggs.edit', $egg)->with('success', "Egg '{$egg->name}' berhasil diimport dari JSON.");
    }
}
