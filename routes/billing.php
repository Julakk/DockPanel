<?php

use App\Http\Controllers\ServerExpiryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'root_admin'])->group(function () {
    Route::put('servers/{server}/expiry', [ServerExpiryController::class, 'update'])->name('servers.expiry.update');
});
