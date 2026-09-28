<?php

namespace App\Console\Commands;

use App\Models\Server;
use Illuminate\Console\Command;

class SuspendExpiredServers extends Command
{
    protected $signature = 'servers:suspend-expired';

    protected $description = 'Suspend server yang sudah lewat expires_at';

    public function handle(): int
    {
        $count = 0;

        Server::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->where('suspended', false)
            ->each(function (Server $server) use (&$count) {
                $server->forceFill([
                    'suspended' => true,
                    'suspension_reason' => 'expired',
                ])->save();
                $count++;
            });

        $this->info("{$count} server di-suspend.");

        return self::SUCCESS;
    }
}
