<?php

namespace App\Console\Commands;

use App\Models\ServerDatabase;
use App\Services\DatabaseProvisioner;
use Illuminate\Console\Command;
use RuntimeException;

class SyncServerDatabases extends Command
{
    protected $signature = 'databases:sync';

    protected $description = 'Bikin database dan user MySQL asli buat semua database server yang tercatat (idempoten)';

    public function handle(DatabaseProvisioner $provisioner): int
    {
        if (! $provisioner->enabled()) {
            $this->warn('Provisioning database dimatikan (DB_PROVISION=false).');

            return self::SUCCESS;
        }

        $ok = 0;
        $failed = 0;

        ServerDatabase::with('databaseHost')->each(function (ServerDatabase $db) use ($provisioner, &$ok, &$failed) {
            try {
                $provisioner->create($db);
                $ok++;
                $this->line("OK     {$db->database}");
            } catch (RuntimeException $e) {
                $failed++;
                $this->error("GAGAL  {$db->database}: {$e->getMessage()}");
            }
        });

        $this->info("{$ok} database siap, {$failed} gagal.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
