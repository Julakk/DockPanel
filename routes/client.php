<?php

use App\Http\Controllers\ClientServerController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('client/servers/{server}')->name('client.servers.')->group(function () {
    Route::get('/', [ClientServerController::class, 'show'])->name('show');
    Route::get('resources', [ClientServerController::class, 'resources'])->name('resources');
    Route::post('power', [ClientServerController::class, 'power'])->name('power');
    Route::post('command', [ClientServerController::class, 'command'])->name('command');
});
