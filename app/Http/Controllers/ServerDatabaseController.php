<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\ServerDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServerDatabaseController extends Controller
{
    public function store(Request $request, Server $server)
    {
        $validated = $request->validate([
            'database_host_id' => 'required|exists:database_hosts,id',
            'database_name' => 'required|string|max:48|regex:/^[a-zA-Z0-9_]+$/',
        ]);

        // Nama database dinamespace pakai uuid_short server biar nggak bentrok antar server
        $fullDatabaseName = "s{$server->uuid_short}_{$validated['database_name']}";
        $username = "u{$server->uuid_short}";
        $plainPassword = Str::random(24);

        $exists = ServerDatabase::where('database_host_id', $validated['database_host_id'])
            ->where('database', $fullDatabaseName)
            ->exists();

        if ($exists) {
            return back()->withErrors(['database_name' => 'Nama database ini udah dipakai di host itu.']);
        }

        ServerDatabase::create([
            'server_id' => $server->id,
            'database_host_id' => $validated['database_host_id'],
            'database' => $fullDatabaseName,
            'username' => $username,
            'password' => $plainPassword,
        ]);

        // TODO: begitu Wings/host beneran aktif, eksekusi CREATE DATABASE + CREATE USER asli di sini

        return back()->with('success', "Database '{$fullDatabaseName}' dibuat. Password: {$plainPassword} (simpan sekarang, nggak ditampilin lagi!)");
    }

    public function destroy(Server $server, ServerDatabase $database)
    {
        // TODO: begitu Wings/host beneran aktif, DROP DATABASE asli di sini
        $name = $database->database;
        $database->delete();

        return back()->with('success', "Database '{$name}' dihapus.");
    }
}
