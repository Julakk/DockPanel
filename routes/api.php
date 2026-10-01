<?php

use App\Http\Controllers\Remote\SftpAuthController;
use App\Http\Controllers\ServerPowerController;
use App\Http\Middleware\AuthenticateNode;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {

    Route::prefix('servers/{server}')->group(function () {
        Route::post('power', ServerPowerController::class);

        // TODO: tambah route lain sesuai fitur:
        // Route::get('files', ServerFileListController::class);
        // Route::post('files/upload', ServerFileUploadController::class);
        // Route::get('console/token', ServerConsoleTokenController::class);
    });

    // TODO admin routes buat CRUD node/egg/nest (middleware role admin)
});

// Dipanggil daemon Wings buat verifikasi login SFTP (auth: Bearer daemon_token node)
Route::post('remote/sftp/auth', SftpAuthController::class)
    ->middleware(AuthenticateNode::class);
