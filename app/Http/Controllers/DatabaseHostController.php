<?php

namespace App\Http\Controllers;

use App\Models\DatabaseHost;
use App\Models\Node;
use App\Services\DatabaseProvisioner;
use App\Services\PhpMyAdminSignon;
use Illuminate\Http\Request;
use RuntimeException;

class DatabaseHostController extends Controller
{
    public function index()
    {
        $hosts = DatabaseHost::with('node')->withCount('databases')->orderBy('name')->get();

        return view('databases.index', compact('hosts'));
    }

    public function create()
    {
        $nodes = Node::orderBy('name')->get();

        return view('databases.create', compact('nodes'));
    }

    public function store(Request $request)
    {
        $host = DatabaseHost::create($this->validated($request, true));

        return redirect()->route('databases.index')->with('success', "Database host '{$host->name}' dibuat.");
    }

    public function edit(DatabaseHost $database, PhpMyAdminSignon $signon)
    {
        $nodes = Node::orderBy('name')->get();
        $database->load('databases.server');

        return view('databases.edit', [
            'host' => $database,
            'nodes' => $nodes,
            'pmaEnabled' => $signon->enabled(),
        ]);
    }

    public function update(Request $request, DatabaseHost $database)
    {
        $validated = $this->validated($request, false);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $database->update($validated);

        return redirect()->route('databases.index')->with('success', "Database host '{$database->name}' diupdate.");
    }

    public function destroy(DatabaseHost $database)
    {
        $count = $database->databases()->count();
        if ($count > 0) {
            return back()->with('error', "Host ini masih punya {$count} database. Hapus database-nya dulu supaya nggak tertinggal di MySQL.");
        }

        $name = $database->name;
        $database->delete();

        return redirect()->route('databases.index')->with('success', "Database host '{$name}' dihapus.");
    }

    /** Tes login Panel ke Database Host. */
    public function test(DatabaseHost $database, DatabaseProvisioner $provisioner)
    {
        try {
            $version = $provisioner->ping($database);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Koneksi ke '{$database->name}' berhasil (MySQL/MariaDB {$version}).");
    }

    /** Buka phpMyAdmin sebagai user Database Host (admin), lewat token sekali pakai. */
    public function phpmyadmin(DatabaseHost $database, PhpMyAdminSignon $signon)
    {
        if (! $signon->enabled()) {
            return back()->with('error', 'phpMyAdmin belum dikonfigurasi (PHPMYADMIN_URL dan PMA_SIGNON_SECRET di .env).');
        }

        return redirect()->away($signon->urlForHost($database));
    }

    private function validated(Request $request, bool $passwordRequired): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'public_host' => 'nullable|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'password' => ($passwordRequired ? 'required' : 'nullable').'|string',
            'node_id' => 'nullable|exists:nodes,id',
        ]);
    }
}
