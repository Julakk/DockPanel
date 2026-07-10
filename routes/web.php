<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EggController;
use App\Http\Controllers\NestController;
use App\Http\Controllers\NodeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('root_admin')->group(function () {
        Route::resource('nodes', NodeController::class);
        Route::resource('nests', NestController::class)->except(['show']);

        Route::get('eggs/import', [EggController::class, 'importForm'])->name('eggs.import.form');
        Route::post('eggs/import', [EggController::class, 'import'])->name('eggs.import');
        Route::post('eggs/{egg}/variables', [EggController::class, 'storeVariable'])->name('eggs.variables.store');
        Route::delete('eggs/{egg}/variables/{variable}', [EggController::class, 'destroyVariable'])->name('eggs.variables.destroy');
        Route::resource('eggs', EggController::class)->except(['show']);
    });
});

// TODO: route CRUD server (middleware 'auth' + 'root_admin' buat admin-only)
