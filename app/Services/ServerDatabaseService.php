<?php

namespace App\Services;

use App\Models\DatabaseHost;
use App\Models\Server;
use App\Models\ServerDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Siklus hidup database server: bikin, ganti password, hapus. Dipakai bareng
 * oleh area admin dan client supaya aturannya sama.
 */
class ServerDatabaseService
{
    public function __construct(private DatabaseProvisioner $provisioner) {}

    /**
     * Bikin database baru dengan user MySQL sendiri (satu user per database),
     * jadi ganti password atau hapus satu database nggak ngaruh ke yang lain.
     *
     * @throws RuntimeException kalau nama bentrok atau host MySQL menolak
     */
    public function create(Server $server, DatabaseHost $host, string $name, string $remote = '%'): ServerDatabase
    {
        $full = "s{$server->uuid_short}_{$name}";

        if (ServerDatabase::where('database_host_id', $host->id)->where('database', $full)->exists()) {
            throw new RuntimeException('Nama database ini udah dipakai di host itu.');
        }

        $database = new ServerDatabase([
            'server_id' => $server->id,
            'database_host_id' => $host->id,
            'database' => $full,
            'username' => "u{$server->uuid_short}_".Str::lower(Str::random(6)),
            'password' => Str::random(24),
            'remote' => $remote !== '' ? $remote : '%',
        ]);
        $database->setRelation('databaseHost', $host);

        $this->provisioner->create($database);
        $database->save();

        return $database;
    }

    /**
     * Ganti password akun MySQL database ini. Database lama (sebelum 0.17.0)
     * yang berbagi akun yang sama ikut diperbarui supaya tetap bisa login.
     */
    public function rotatePassword(ServerDatabase $database): string
    {
        $database->loadMissing('databaseHost');
        $new = Str::random(24);

        $shared = ServerDatabase::where('database_host_id', $database->database_host_id)
            ->where('username', $database->username)
            ->where('remote', $database->remote)
            ->get();

        DB::transaction(function () use ($database, $shared, $new) {
            foreach ($shared as $row) {
                $row->update(['password' => $new]);
            }
            $this->provisioner->rotatePassword($database, $new);
        });

        return $new;
    }

    public function delete(ServerDatabase $database): void
    {
        $this->provisioner->drop($database->loadMissing('databaseHost'));
        $database->delete();
    }
}
