<?php

namespace App\Http\Controllers;

use App\Models\DatabaseHost;
use App\Models\Server;
use App\Models\ServerDatabase;
use App\Services\DatabaseProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

class ServerDatabaseController extends Controller
{
    public function store(Request $request, Server $server, DatabaseProvisioner $provisioner)
    {
        $validated = $request->validate([
            'database_host_id' => 'required|exists:database_hosts,id',
            'database_name' => 'required|string|max:48|regex:/^[a-zA-Z0-9_]+$/',
            'remote' => ['nullable', 'string', 'max:60', 'regex:/^[A-Za-z0-9._%:-]+$/'],
        ]);

        // Nama database dinamespace pakai uuid_short server biar nggak bentrok antar server
        $fullDatabaseName = "s{$server->uuid_short}_{$validated['database_name']}";
        $username = "u{$server->uuid_short}";
        $remote = $validated['remote'] ?? '%';

        $exists = ServerDatabase::where('database_host_id', $validated['database_host_id'])
            ->where('database', $fullDatabaseName)
            ->exists();

        if ($exists) {
            return back()->withErrors(['database_name' => 'Nama database ini udah dipakai di host itu.']);
        }

        // Satu user MySQL dipakai semua database server ini di host yang sama,
        // jadi password-nya harus tetap sama (kalau nggak, database lama ikut putus).
        $sameUser = ServerDatabase::where('database_host_id', $validated['database_host_id'])
            ->where('username', $username)
            ->where('remote', $remote)
            ->first();
        $plainPassword = $sameUser ? (string) $sameUser->password : Str::random(24);

        $database = new ServerDatabase([
            'server_id' => $server->id,
            'database_host_id' => $validated['database_host_id'],
            'database' => $fullDatabaseName,
            'username' => $username,
            'password' => $plainPassword,
            'remote' => $remote,
        ]);
        $database->setRelation('databaseHost', DatabaseHost::findOrFail($validated['database_host_id']));

        try {
            $provisioner->create($database);
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['database_name' => $e->getMessage()]);
        }

        $database->save();

        return back()->with('success', "Database '{$fullDatabaseName}' dibuat. Password: {$plainPassword} (simpan sekarang, nggak ditampilin lagi!)");
    }

    public function destroy(Server $server, ServerDatabase $database, DatabaseProvisioner $provisioner)
    {
        try {
            $provisioner->drop($database->loadMissing('databaseHost'));
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $name = $database->database;
        $database->delete();

        return back()->with('success', "Database '{$name}' dihapus.");
    }
}
