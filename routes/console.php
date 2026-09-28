<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// TODO: tambah command custom, contoh:
// Artisan::command('dockpanel:node:token {node}', function () { ... });

Schedule::command('servers:notify-expiring')->dailyAt('08:00');
Schedule::command('servers:suspend-expired')->everyFiveMinutes();

Schedule::command('servers:run-schedules')->everyMinute();
