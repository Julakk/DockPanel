<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Services\ProvisionService;
use Illuminate\Console\Command;

class RetryProvisioning extends Command
{
    protected $signature = 'servers:retry-provision';

    protected $description = 'Coba ulang provision ke Wings buat server yang sebelumnya gagal';

    public function handle(ProvisionService $provisioner): int
    {
        $servers = Server::query()
            ->whereNotNull('provision_next_at')
            ->where('provision_next_at', '<=', now())
            ->where('provision_attempts', '<', ProvisionService::MAX_ATTEMPTS)
            ->get();

        foreach ($servers as $server) {
            [$ok, $err] = $provisioner->attempt($server);
            $this->line(($ok ? 'OK   ' : 'FAIL ')."{$server->uuid}".($err ? " — {$err}" : ''));
        }

        return self::SUCCESS;
    }
}
