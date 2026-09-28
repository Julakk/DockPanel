<?php

namespace App\Console\Commands;

use App\Models\Server;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class NotifyExpiringServers extends Command
{
    protected $signature = 'servers:notify-expiring {--days=3 : Kirim pengingat H-sekian hari}';

    protected $description = 'Kirim email pengingat ke owner server yang segera expired';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $sent = 0;

        $servers = Server::with('owner')
            ->whereNotNull('expires_at')
            ->where('suspended', false)
            ->whereNull('expiry_notified_at')
            ->whereBetween('expires_at', [now(), now()->addDays($days)])
            ->get();

        foreach ($servers as $server) {
            if (! $server->owner) {
                continue;
            }

            $text = "Halo {$server->owner->name},\n\n"
                ."Server \"{$server->name}\" akan expired pada "
                .$server->expires_at->format('d M Y H:i').".\n"
                ."Perpanjang sebelum waktu tersebut supaya server tidak disuspend.\n";

            try {
                Mail::raw($text, function ($m) use ($server) {
                    $m->to($server->owner->email)->subject("Server {$server->name} segera expired");
                });
                $server->forceFill(['expiry_notified_at' => now()])->save();
                $sent++;
            } catch (\Throwable $e) {
                $this->warn("Gagal kirim ke {$server->owner->email}: ".$e->getMessage());
            }
        }

        $this->info("{$sent} pengingat terkirim.");

        return self::SUCCESS;
    }
}
