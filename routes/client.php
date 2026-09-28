<?php

use App\Http\Controllers\ClientServerController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('client/servers/{server}')->name('client.servers.')->group(function () {
    Route::get('/', [ClientServerController::class, 'show'])->name('show');
    Route::get('resources', [ClientServerController::class, 'resources'])->name('resources');
    Route::post('power', [ClientServerController::class, 'power'])->name('power');
    Route::post('command', [ClientServerController::class, 'command'])->name('command');
    Route::put('rename', [ClientServerController::class, 'rename'])->name('rename');
    Route::put('startup', [ClientServerController::class, 'updateStartup'])->name('startup.update');
    Route::post('users', [ClientServerController::class, 'addUser'])->name('users.store');
    Route::delete('users/{user}', [ClientServerController::class, 'removeUser'])->name('users.destroy');
});
