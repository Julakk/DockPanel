<?php

use App\Http\Controllers\ClientDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->get('client', [ClientDashboardController::class, 'index'])->name('client.index');
