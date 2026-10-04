<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesAllocations;
use App\Models\ActivityLog;
use App\Models\Allocation;
use App\Models\Backup;
use App\Models\DatabaseHost;
use App\Models\Node;
use App\Models\Server;
use App\Models\ServerDatabase;
use App\Models\User;
use App\Services\PhpMyAdminSignon;
use App\Services\ServerDatabaseService;
use App\Services\ServerResourceService;
use App\Services\WingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

class ClientServerController extends Controller
{
    use AppliesAllocations;

    /** key tab => [label, permission subuser yang dibutuhkan (null = semua yang punya akses)] */
    private const TABS = [
        'console' => ['Console', 'console.access'],
        'files' => ['Files', 'files.read'],
        'databases' => ['Databases', 'database.view'],
        'schedules' => ['Schedules', null],
        'users' => ['Users', 'manage'],
        'backups' => ['Backups', null],
        'network' => ['Network', null],
        'startup' => ['Startup', null],
        'settings' => ['Settings', null],
        'activity' => ['Activity', null],
    ];

    private function isManager(Request $request, Server $server): bool
    {
        $user = $request->user();

        return (bool) ($user->root_admin ?? false) || (int) $server->owner_id === (int) $user->id;
    }

    /**
     * null = akses penuh (admin/owner). Array = daftar permission subuser.
     * Bukan admin/owner/subuser => 403.
     */
    private function permissions(Request $request, Server $server): ?array
    {
        if ($this->isManager($request, $server)) {
            return null;
        }

        $sub = $server->subusers()->where('users.id', $request->user()->id)->first();
        abort_unless($sub, 403);

        $perms = json_decode($sub->pivot->permissions ?? '[]', true);

        return is_array($perms) ? $perms : [];
    }

    private function can(Request $request, Server $server, ?string $perm): bool
    {
        if ($perm === null) {
            $this->permissions($request, $server);

            return true;
        }

        if ($perm === 'manage') {
            return $this->isManager($request, $server);
        }

        $perms = $this->permissions($request, $server);

        return $perms === null || in_array($perm, $perms, true);
    }

    private function authorizeAccess(Request $request, Server $server): void
    {
        $this->permissions($request, $server);
    }

    private function requireManager(Request $request, Server $server): void
    {
        abort_unless($this->isManager($request, $server), 403);
    }

    public function show(Request $request, Server $server)
    {
        $this->authorizeAccess($request, $server);

        $tabs = [];
        foreach (self::TABS as $key => [$label, $perm]) {
            if ($this->can($request, $server, $perm)) {
                $tabs[$key] = $label;
            }
        }

        $tab = $request->query('tab', 'console');
        if (! isset($tabs[$tab])) {
            $tab = array_key_first($tabs);
        }

        $server->load(['node', 'egg', 'primaryAllocation', 'allocations']);

        $this->syncRestoreState($server);

        $data = [];
        if ($tab === 'databases') {
            $server->load('databases.databaseHost');
        } elseif ($tab === 'users') {
            $server->load(['owner', 'subusers']);
            $data['availablePermissions'] = ServerSubuserController::AVAILABLE_PERMISSIONS;
        } elseif ($tab === 'startup') {
            $server->load('serverVariables.eggVariable');
            $data['isAdmin'] = (bool) ($request->user()->root_admin ?? false);
        } elseif ($tab === 'backups') {
            $backups = $this->syncedBackups($server);
            $data['backups'] = $backups;
            $data['backupLimit'] = (int) $server->backup_limit;
            $data['backupUsed'] = $backups->whereIn('status', ['creating', 'completed'])->count();
        } elseif ($tab === 'activity') {
            $data['activities'] = ActivityLog::with('user')
                ->where('server_id', $server->id)
                ->latest()->limit(30)->get();
        }

        return view('client.server', array_merge($data, [
            'server' => $server,
            'tab' => $tab,
            'tabs' => $tabs,
            'isManager' => $this->isManager($request, $server),
            'canStart' => $this->can($request, $server, 'control.start'),
            'canStop' => $this->can($request, $server, 'control.stop'),
            'canRestart' => $this->can($request, $server, 'control.restart'),
        ]));
    }

    public function resources(Request $request, Server $server, ServerResourceService $resources): JsonResponse
    {
        $this->authorizeAccess($request, $server);

        return response()->json($resources->for($server));
    }

    /**
     * Token JWT short-lived buat browser buka WebSocket console langsung ke Wings.
     * Dipanggil dari JS sebelum bikin koneksi ws://, bukan dipakai server-side.
     */
    public function consoleToken(Request $request, Server $server): JsonResponse
    {
        $this->authorizeAccess($request, $server);

        if (! $server->node) {
            return response()->json(['error' => 'Node belum di-set buat server ini.'], 422);
        }

        $wings = new WingsService($server->loadMissing('node'));

        return response()->json([
            'token' => $wings->generateWebsocketToken(),
            'ws_host' => $server->node->fqdn,
            'ws_port' => $server->node->daemon_listen,
        ]);
    }

    public function power(Request $request, Server $server)
    {
        $data = $request->validate([
            'action' => ['required', 'in:start,stop,restart,kill'],
        ]);

        $perm = match ($data['action']) {
            'start' => 'control.start',
            'restart' => 'control.restart',
            default => 'control.stop',
        };
        abort_unless($this->can($request, $server, $perm), 403);

        if ($server->suspended) {
            return back()->with('error', 'Server lagi di-suspend, power action dimatiin.');
        }

        $this->syncRestoreState($server);
        if ($server->status === 'restoring_backup') {
            return back()->with('error', 'Restore backup lagi berjalan, power action dimatiin.');
        }

        try {
            $ok = (new WingsService($server->loadMissing('node')))->power($data['action']);
        } catch (\Throwable $e) {
            $ok = false;
        }

        ActivityLog::record('server:power', ['action' => $data['action'], 'ok' => $ok], $server);

        return $ok
            ? back()->with('success', "Power action '{$data['action']}' dikirim ke Wings.")
            : back()->with('error', 'Gagal hubungi Wings. Node belum aktif atau nggak bisa dijangkau.');
    }

    public function command(Request $request, Server $server)
    {
        abort_unless($this->can($request, $server, 'console.access'), 403);

        $data = $request->validate([
            'command' => ['required', 'string', 'max:255'],
        ]);

        if ($server->suspended) {
            return back()->with('error', 'Server lagi di-suspend.');
        }

        try {
            $ok = (new WingsService($server->loadMissing('node')))->sendCommand($data['command']);
        } catch (\Throwable $e) {
            $ok = false;
        }

        return $ok
            ? back()->with('success', 'Command terkirim.')
            : back()->with('error', 'Gagal kirim command. Wings belum aktif?');
    }

    public function rename(Request $request, Server $server)
    {
        $this->requireManager($request, $server);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $server->update($data);
        ActivityLog::record('server:rename', ['name' => $data['name']], $server);

        return redirect()
            ->route('client.servers.show', ['server' => $server, 'tab' => 'settings'])
            ->with('success', 'Detail server diupdate.');
    }

    public function updateStartup(Request $request, Server $server)
    {
        $this->requireManager($request, $server);

        $isAdmin = (bool) ($request->user()->root_admin ?? false);
        $server->load('serverVariables.eggVariable');
        $errors = [];

        foreach ($server->serverVariables as $sv) {
            $ev = $sv->eggVariable;
            if (! $ev || (! $isAdmin && ! $ev->user_editable)) {
                continue;
            }
            if (! $request->has("variables.{$ev->id}")) {
                continue;
            }

            $value = (string) $request->input("variables.{$ev->id}", '');
            $rules = $ev->rules ?: 'nullable|string|max:255';

            try {
                $v = Validator::make([$ev->env_variable => $value], [$ev->env_variable => $rules]);
                $failed = $v->fails();
                $msg = $failed ? $v->errors()->first() : null;
            } catch (\Throwable $e) {
                $failed = mb_strlen($value) > 255;
                $msg = 'Nilai terlalu panjang.';
            }

            if ($failed) {
                $errors[] = "{$ev->name}: {$msg}";

                continue;
            }

            $sv->update(['variable_value' => $value]);
        }

        if ($errors) {
            return back()->with('error', implode(' | ', $errors));
        }

        ActivityLog::record('server:startup', [], $server);

        return back()->with('success', 'Variable startup diupdate.');
    }

    public function makePrimary(Request $request, Server $server, $allocation)
    {
        $this->requireManager($request, $server);

        $alloc = $server->allocations()->findOrFail($allocation);

        DB::transaction(function () use ($server, $alloc) {
            Allocation::where('server_id', $server->id)->update(['is_primary' => false]);
            $alloc->update(['is_primary' => true]);
        });
        ActivityLog::record('server:allocation.primary', ['allocation' => "{$alloc->ip}:{$alloc->port}"], $server);
        [$key, $message] = $this->allocationFlash($server, 'Allocation primary diganti.');

        return redirect()
            ->route('client.servers.show', ['server' => $server, 'tab' => 'network'])
            ->with($key, $message);
    }

    public function updateAllocationNotes(Request $request, Server $server, $allocation)
    {
        $this->requireManager($request, $server);

        $data = $request->validate(['notes' => ['nullable', 'string', 'max:255']]);
        $alloc = $server->allocations()->findOrFail($allocation);
        $alloc->update(['notes' => $data['notes'] ?? null]);

        return redirect()
            ->route('client.servers.show', ['server' => $server, 'tab' => 'network'])
            ->with('success', 'Notes disimpan.');
    }

    public function destroyAllocation(Request $request, Server $server, $allocation)
    {
        $this->requireManager($request, $server);

        $alloc = $server->allocations()->findOrFail($allocation);

        if ($alloc->is_primary) {
            return back()->with('error', 'Allocation primary nggak bisa dihapus. Jadikan allocation lain primary dulu.');
        }

        $alloc->update(['server_id' => null, 'is_primary' => false]);
        ActivityLog::record('server:allocation.remove', ['allocation' => "{$alloc->ip}:{$alloc->port}"], $server);
        [$key, $message] = $this->allocationFlash($server, 'Allocation dilepas dari server.');

        return redirect()
            ->route('client.servers.show', ['server' => $server, 'tab' => 'network'])
            ->with($key, $message);
    }

    private function wingsFor(Server $server): WingsService
    {
        $server->loadMissing('node');
        abort_unless($server->node, 422, 'Node belum di-set buat server ini.');

        return new WingsService($server);
    }

    /** Pesan error yang enak dibaca dari balasan Wings buat operasi backup. */
    private function backupError(int $status, array $res): string
    {
        $error = $res['error'] ?? null;

        if ($status === 404 && str_contains((string) $error, 'daemon ini')) {
            return 'Server belum di-provision ke Wings. Minta admin klik Provision di halaman server.';
        }
        if ($status === 404 && $error !== null) {
            return $error;
        }
        if ($status === 404 || $status === 405) {
            return 'Wings belum mendukung backup (butuh DockWings v0.4.1+).';
        }
        if ($status === 401) {
            return 'Token node ditolak Wings. Cek daemon_token di node.';
        }

        return $error ?: "Wings balikin HTTP {$status}.";
    }

    /** Daftar backup server; yang masih "creating" disinkronkan dulu dengan Wings. */
    private function syncedBackups(Server $server)
    {
        $backups = $server->backups()->latest('id')->get();
        $server->loadMissing('node');

        if (! $server->node) {
            return $backups;
        }

        foreach ($backups->where('status', 'creating') as $backup) {
            try {
                [$status, $res] = (new WingsService($server))->backupStatus($backup->uuid);
            } catch (\Throwable $e) {
                break; // Wings lagi nggak terjangkau, coba lagi di muat berikutnya
            }

            if ($status === 200) {
                $new = in_array($res['status'] ?? null, Backup::STATUSES, true) ? $res['status'] : 'creating';
                $backup->update([
                    'status' => $new,
                    'size' => (int) ($res['size'] ?? 0),
                    'checksum' => $res['checksum'] ?? null,
                    'error' => $res['error'] ?? null,
                    'completed_at' => $res['completed_at'] ?? null,
                ]);
            } elseif ($status === 404) {
                $backup->update(['status' => 'failed', 'error' => 'Backup hilang dari Wings (daemon restart?).']);
            }
        }

        return $backups;
    }

    public function storeBackup(Request $request, Server $server)
    {
        $this->requireManager($request, $server);

        $data = $request->validate(['name' => ['nullable', 'string', 'max:100']]);
        $back = redirect()->route('client.servers.show', ['server' => $server, 'tab' => 'backups']);

        if ($server->suspended) {
            return $back->with('error', 'Server lagi di-suspend.');
        }

        $limit = (int) $server->backup_limit;
        if ($limit === 0) {
            return $back->with('error', 'Backup tidak bisa dibuat karena limit backup diset 0.');
        }
        if ($server->backups()->whereIn('status', ['creating', 'completed'])->count() >= $limit) {
            return $back->with('error', "Limit backup tercapai ({$limit}). Hapus backup lama dulu.");
        }

        $wings = $this->wingsFor($server);
        $name = trim((string) ($data['name'] ?? ''));

        $backup = $server->backups()->create([
            'uuid' => (string) Str::uuid(),
            'name' => $name !== '' ? $name : 'Backup '.now()->format('d M Y H:i'),
            'status' => 'creating',
        ]);

        try {
            [$status, $res] = $wings->createBackup($backup->uuid);
            $error = $status === 202 ? null : $this->backupError($status, $res);
        } catch (\Throwable $e) {
            $error = 'Wings nggak bisa dihubungi: '.Node::explainWingsError($e->getMessage(), (string) $server->node->scheme);
        }

        if ($error !== null) {
            $backup->update(['status' => 'failed', 'error' => $error]);

            return $back->with('error', $error);
        }

        ActivityLog::record('server:backup.create', ['name' => $backup->name], $server);

        return $back->with('success', 'Backup dibuat di background. Halaman ini nyegerin status otomatis.');
    }

    public function downloadBackup(Request $request, Server $server, Backup $backup)
    {
        $this->requireManager($request, $server);
        abort_unless((int) $backup->server_id === (int) $server->id, 404);
        abort_unless($backup->status === 'completed', 404);

        $back = redirect()->route('client.servers.show', ['server' => $server, 'tab' => 'backups']);

        try {
            $resp = $this->wingsFor($server)->downloadBackup($backup->uuid);
        } catch (\Throwable $e) {
            return $back->with('error', 'Wings nggak bisa dijangkau.');
        }

        if ($resp->status() !== 200) {
            return $back->with('error', 'Backup nggak ketemu di Wings (HTTP '.$resp->status().').');
        }

        ActivityLog::record('server:backup.download', ['name' => $backup->name], $server);

        $body = $resp->toPsrResponse()->getBody();
        $file = (Str::slug($backup->name) ?: $backup->uuid).'.tar.gz';

        return response()->streamDownload(function () use ($body) {
            while (! $body->eof()) {
                echo $body->read(8192);
                flush();
            }
        }, $file, ['Content-Type' => 'application/gzip']);
    }

    public function destroyBackup(Request $request, Server $server, Backup $backup)
    {
        $this->requireManager($request, $server);
        abort_unless((int) $backup->server_id === (int) $server->id, 404);

        $back = redirect()->route('client.servers.show', ['server' => $server, 'tab' => 'backups']);
        $stale = $backup->created_at && $backup->created_at->lt(now()->subHour());

        if ($backup->status === 'creating' && ! $stale) {
            return $back->with('error', 'Backup masih diproses. Tunggu selesai dulu.');
        }

        $force = $backup->status !== 'completed';

        try {
            [$status, $res] = $this->wingsFor($server)->deleteBackup($backup->uuid);
        } catch (\Throwable $e) {
            if (! $force) {
                return $back->with('error', 'Wings nggak bisa dijangkau, backup nggak dihapus.');
            }
            $status = 0;
            $res = [];
        }

        if (! $force && ! in_array($status, [200, 404], true)) {
            return $back->with('error', $this->backupError($status, $res));
        }

        $name = $backup->name;
        $backup->delete();
        ActivityLog::record('server:backup.delete', ['name' => $name], $server);

        return $back->with('success', "Backup '{$name}' dihapus.");
    }

    public function storeDatabase(Request $request, Server $server, ServerDatabaseService $databases)
    {
        $this->requireManager($request, $server);

        $data = $request->validate([
            'database_name' => ['required', 'string', 'max:48', 'regex:/^[a-zA-Z0-9_]+$/'],
            'remote' => ['nullable', 'string', 'max:60', 'regex:/^[A-Za-z0-9._%:-]+$/'],
        ]);
        $back = redirect()->route('client.servers.show', ['server' => $server, 'tab' => 'databases']);

        $limit = $server->database_limit;
        if ($limit !== null && (int) $limit === 0) {
            return $back->with('error', 'Pembuatan database dimatikan untuk server ini. Hubungi admin.');
        }
        if ($limit !== null && $server->databases()->count() >= (int) $limit) {
            return $back->with('error', "Limit database tercapai ({$limit}). Hapus yang lama dulu.");
        }

        $host = DatabaseHost::forServer($server);
        if (! $host) {
            return $back->with('error', 'Belum ada Database Host yang tersedia buat node server ini. Hubungi admin.');
        }

        try {
            $database = $databases->create($server, $host, $data['database_name'], $data['remote'] ?? '%');
        } catch (RuntimeException $e) {
            return $back->with('error', $e->getMessage());
        }

        ActivityLog::record('server:database.create', ['name' => $database->database], $server);

        return $back->with('success', "Database '{$database->database}' dibuat.");
    }

    public function rotateDatabasePassword(Request $request, Server $server, ServerDatabase $database, ServerDatabaseService $databases)
    {
        $this->requireManager($request, $server);
        abort_unless((int) $database->server_id === (int) $server->id, 404);

        $back = redirect()->route('client.servers.show', ['server' => $server, 'tab' => 'databases']);

        try {
            $databases->rotatePassword($database);
        } catch (RuntimeException $e) {
            return $back->with('error', $e->getMessage());
        }

        ActivityLog::record('server:database.rotate', ['name' => $database->database], $server);

        return $back->with('success', "Password database '{$database->database}' diganti. Update config game server kamu.");
    }

    public function openPhpMyAdmin(Request $request, Server $server, ServerDatabase $database, PhpMyAdminSignon $signon)
    {
        $this->requireManager($request, $server);
        abort_unless((int) $database->server_id === (int) $server->id, 404);

        if (! $signon->enabled()) {
            return redirect()
                ->route('client.servers.show', ['server' => $server, 'tab' => 'databases'])
                ->with('error', 'phpMyAdmin belum dikonfigurasi. Hubungi admin.');
        }

        ActivityLog::record('server:database.phpmyadmin', ['name' => $database->database], $server);

        return redirect()->away($signon->urlForDatabase($database));
    }

    public function destroyDatabase(Request $request, Server $server, ServerDatabase $database, ServerDatabaseService $databases)
    {
        $this->requireManager($request, $server);
        abort_unless((int) $database->server_id === (int) $server->id, 404);

        $name = $database->database;

        try {
            $databases->delete($database);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        ActivityLog::record('server:database.delete', ['name' => $name], $server);

        return redirect()
            ->route('client.servers.show', ['server' => $server, 'tab' => 'databases'])
            ->with('success', "Database '{$name}' dihapus.");
    }

    /**
     * Server yang lagi "restoring_backup": tanya Wings, lalu balikin ke "offline"
     * kalau restore sudah selesai, gagal, atau statusnya hilang (daemon restart).
     */
    private function syncRestoreState(Server $server): void
    {
        if ($server->status !== 'restoring_backup' || ! $server->loadMissing('node')->node) {
            return;
        }

        try {
            [$status, $res] = (new WingsService($server))->restoreStatus();
        } catch (\Throwable $e) {
            return;
        }

        $state = $res['status'] ?? null;
        if ($status !== 200 || $state === 'restoring') {
            return;
        }

        $server->update(['status' => 'offline']);

        if ($state === 'completed') {
            session()->flash('success', 'Restore selesai. File server sudah dikembalikan dari backup.');
        } elseif ($state === 'failed') {
            session()->flash('error', 'Restore gagal: '.($res['error'] ?? 'alasan nggak diketahui').'. File server nggak berubah.');
        } else {
            session()->flash('error', 'Status restore hilang (daemon restart?). Cek file server, lalu ulangi restore kalau perlu.');
        }
    }

    public function restoreBackup(Request $request, Server $server, Backup $backup)
    {
        $this->requireManager($request, $server);
        abort_unless((int) $backup->server_id === (int) $server->id, 404);
        abort_unless($backup->status === 'completed', 404);

        $back = redirect()->route('client.servers.show', ['server' => $server, 'tab' => 'backups']);

        if ($server->suspended) {
            return $back->with('error', 'Server lagi di-suspend.');
        }
        if ($server->status === 'restoring_backup') {
            return $back->with('error', 'Restore lain masih berjalan.');
        }

        try {
            [$status, $res] = $this->wingsFor($server)->restoreBackup($backup->uuid);
        } catch (\Throwable $e) {
            return $back->with('error', 'Wings nggak bisa dihubungi: '.Node::explainWingsError($e->getMessage(), (string) $server->node->scheme));
        }

        if (($status === 404 && ! isset($res['error'])) || $status === 405) {
            return $back->with('error', 'Wings belum mendukung restore (butuh DockWings v0.4.2+).');
        }
        if ($status !== 202) {
            return $back->with('error', $this->backupError($status, $res));
        }

        $server->update(['status' => 'restoring_backup']);
        ActivityLog::record('server:backup.restore', ['name' => $backup->name], $server);

        return $back->with('success', 'Restore dimulai. Server dikunci sampai selesai.');
    }

    public function addUser(Request $request, Server $server)
    {
        $this->requireManager($request, $server);

        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'permissions' => 'nullable|array',
            'permissions.*' => Rule::in(array_keys(ServerSubuserController::AVAILABLE_PERMISSIONS)),
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ((int) $user->id === (int) $server->owner_id) {
            return back()->with('error', 'User ini udah jadi owner server.');
        }
        if ($server->subusers()->where('users.id', $user->id)->exists()) {
            return back()->with('error', 'User ini udah jadi subuser di server ini.');
        }

        $server->subusers()->attach($user->id, [
            'permissions' => json_encode($validated['permissions'] ?? []),
        ]);
        ActivityLog::record('server:subuser.add', ['email' => $user->email], $server);

        return redirect()
            ->route('client.servers.show', ['server' => $server, 'tab' => 'users'])
            ->with('success', "{$user->name} ditambahin sebagai subuser.");
    }

    public function removeUser(Request $request, Server $server, User $user)
    {
        $this->requireManager($request, $server);

        $server->subusers()->detach($user->id);
        ActivityLog::record('server:subuser.remove', ['email' => $user->email], $server);

        return redirect()
            ->route('client.servers.show', ['server' => $server, 'tab' => 'users'])
            ->with('success', "{$user->name} dicabut dari server ini.");
    }
}
