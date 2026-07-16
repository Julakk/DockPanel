<?php

use App\Http\Controllers\AccountApiCredentialController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AllocationController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatabaseHostController;
use App\Http\Controllers\EggController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MountController;
use App\Http\Controllers\NestController;
use App\Http\Controllers\NodeController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\ServerDatabaseController;
use App\Http\Controllers\ServerSubuserController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/login/two-factor', [LoginController::class, 'showTwoFactorChallenge'])->name('login.two-factor');
    Route::post('/login/two-factor', [LoginController::class, 'verifyTwoFactorChallenge'])->name('login.two-factor.verify');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('account', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');
    Route::put('account/email', [AccountController::class, 'updateEmail'])->name('account.email.update');

    Route::get('account/two-factor', [AccountController::class, 'twoFactorShow'])->name('account.two-factor.show');
    Route::post('account/two-factor', [AccountController::class, 'twoFactorEnable'])->name('account.two-factor.enable');
    Route::delete('account/two-factor', [AccountController::class, 'twoFactorDisable'])->name('account.two-factor.disable');

    Route::get('account/activity', [AccountController::class, 'activity'])->name('account.activity');

    Route::get('account/api-credentials', [AccountApiCredentialController::class, 'index'])->name('account.api-credentials.index');
    Route::post('account/api-credentials', [AccountApiCredentialController::class, 'store'])->name('account.api-credentials.store');
    Route::delete('account/api-credentials/{tokenId}', [AccountApiCredentialController::class, 'destroy'])->name('account.api-credentials.destroy');

    Route::middleware('root_admin')->group(function () {
        Route::resource('nodes', NodeController::class);
        Route::post('nodes/{node}/allocations', [AllocationController::class, 'store'])->name('nodes.allocations.store');
        Route::delete('nodes/{node}/allocations/{allocation}', [AllocationController::class, 'destroy'])->name('nodes.allocations.destroy');

        Route::resource('nests', NestController::class)->except(['show']);

        Route::get('eggs/import', [EggController::class, 'importForm'])->name('eggs.import.form');
        Route::post('eggs/import', [EggController::class, 'import'])->name('eggs.import');
        Route::post('eggs/{egg}/variables', [EggController::class, 'storeVariable'])->name('eggs.variables.store');
        Route::delete('eggs/{egg}/variables/{variable}', [EggController::class, 'destroyVariable'])->name('eggs.variables.destroy');
        Route::resource('eggs', EggController::class)->except(['show']);

        Route::put('servers/{server}/variables', [ServerController::class, 'updateVariables'])->name('servers.variables.update');
        Route::put('servers/{server}/mounts', [ServerController::class, 'updateMounts'])->name('servers.mounts.update');
        Route::post('servers/{server}/databases', [ServerDatabaseController::class, 'store'])->name('servers.databases.store');
        Route::delete('servers/{server}/databases/{database}', [ServerDatabaseController::class, 'destroy'])->name('servers.databases.destroy');
        Route::post('servers/{server}/subusers', [ServerSubuserController::class, 'store'])->name('servers.subusers.store');
        Route::delete('servers/{server}/subusers/{user}', [ServerSubuserController::class, 'destroy'])->name('servers.subusers.destroy');
        Route::post('servers/{server}/provision', [ServerController::class, 'provision'])->name('servers.provision');
        Route::resource('servers', ServerController::class);

        Route::resource('users', UserController::class)->except(['show']);
        Route::resource('locations', LocationController::class)->except(['show']);
        Route::resource('databases', DatabaseHostController::class)->except(['show']);
        Route::resource('mounts', MountController::class)->except(['show']);

        Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

        Route::get('api-keys', [ApiKeyController::class, 'index'])->name('api-keys.index');
        Route::post('api-keys', [ApiKeyController::class, 'store'])->name('api-keys.store');
        Route::delete('api-keys/{tokenId}', [ApiKeyController::class, 'destroy'])->name('api-keys.destroy');
    });
});
