<?php

use App\Http\Controllers\ServerScheduleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('client/servers/{server}/schedules')->name('client.servers.schedules.')->group(function () {
    Route::post('/', [ServerScheduleController::class, 'store'])->name('store');
    Route::put('{schedule}/toggle', [ServerScheduleController::class, 'toggle'])->name('toggle');
    Route::delete('{schedule}', [ServerScheduleController::class, 'destroy'])->name('destroy');
});
