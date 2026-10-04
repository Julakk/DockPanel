<?php

namespace App\Http\Controllers;

use App\Models\DatabaseHost;
use App\Models\Server;
use App\Models\ServerDatabase;
use App\Services\ServerDatabaseService;
use Illuminate\Http\Request;
use RuntimeException;

class ServerDatabaseController extends Controller
{
    public function store(Request $request, Server $server, ServerDatabaseService $databases)
    {
        $validated = $request->validate([
            'database_host_id' => 'required|exists:database_hosts,id',
            'database_name' => 'required|string|max:48|regex:/^[a-zA-Z0-9_]+$/',
            'remote' => ['nullable', 'string', 'max:60', 'regex:/^[A-Za-z0-9._%:-]+$/'],
        ]);

        try {
            $database = $databases->create(
                $server,
                DatabaseHost::findOrFail($validated['database_host_id']),
                $validated['database_name'],
                $validated['remote'] ?? '%',
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['database_name' => $e->getMessage()]);
        }

        return back()->with('success', "Database '{$database->database}' dibuat. Password: {$database->password} (simpan sekarang, nggak ditampilin lagi!)");
    }

    public function destroy(Server $server, ServerDatabase $database, ServerDatabaseService $databases)
    {
        $name = $database->database;

        try {
            $databases->delete($database);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Database '{$name}' dihapus.");
    }
}
