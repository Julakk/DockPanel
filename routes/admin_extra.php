<?php

use App\Http\Controllers\NodeConfigController;
use App\Http\Controllers\ServerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'root_admin'])->group(function () {
    Route::post('servers/{server}/suspend', [ServerController::class, 'suspend'])->name('servers.suspend');
    Route::post('servers/{server}/unsuspend', [ServerController::class, 'unsuspend'])->name('servers.unsuspend');
    Route::get('nodes/{node}/configuration', [NodeConfigController::class, 'show'])->name('nodes.config');
});
