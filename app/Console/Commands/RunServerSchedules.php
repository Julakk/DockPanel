<?php

namespace App\Console\Commands;

use App\Models\ServerSchedule;
use App\Services\WingsService;
use Cron\CronExpression;
use Illuminate\Console\Command;

class RunServerSchedules extends Command
{
    protected $signature = 'servers:run-schedules';

    protected $description = 'Jalankan schedule server yang jatuh tempo (dipanggil tiap menit)';

    public function handle(): int
    {
        $ran = 0;

        ServerSchedule::with('server.node')->where('is_active', true)->each(function (ServerSchedule $s) use (&$ran) {
            if (! CronExpression::isValidExpression($s->cron)) {
                return;
            }

            if (! (new CronExpression($s->cron))->isDue(now())) {
                return;
            }

            // Cegah dobel-run di menit yang sama.
            if ($s->last_run_at && $s->last_run_at->format('Y-m-d H:i') === now()->format('Y-m-d H:i')) {
                return;
            }

            $server = $s->server;

            if (! $server || $server->suspended) {
                $s->forceFill(['last_run_at' => now(), 'last_status' => 'skipped: suspended'])->save();

                return;
            }

            try {
                $wings = new WingsService($server);
                $ok = $s->action === 'command'
                    ? $wings->sendCommand((string) $s->payload)
                    : $wings->power($s->action);
                $status = $ok ? 'ok' : 'failed: wings menolak';
            } catch (\Throwable $e) {
                $status = 'failed: '.mb_substr($e->getMessage(), 0, 100);
            }

            $s->forceFill(['last_run_at' => now(), 'last_status' => $status])->save();
            $ran++;
        });

        $this->info("{$ran} schedule dijalankan.");

        return self::SUCCESS;
    }
}
