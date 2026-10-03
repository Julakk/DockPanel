<?php

use App\Http\Controllers\ClientFileController;
use App\Http\Controllers\ClientServerController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('client/servers/{server}')->name('client.servers.')->group(function () {
    Route::get('/', [ClientServerController::class, 'show'])->name('show');
    Route::get('resources', [ClientServerController::class, 'resources'])->name('resources');
    Route::get('console-token', [ClientServerController::class, 'consoleToken'])->name('console-token');
    Route::post('power', [ClientServerController::class, 'power'])->name('power');
    Route::post('command', [ClientServerController::class, 'command'])->name('command');
    Route::put('rename', [ClientServerController::class, 'rename'])->name('rename');
    Route::put('startup', [ClientServerController::class, 'updateStartup'])->name('startup.update');
    Route::post('users', [ClientServerController::class, 'addUser'])->name('users.store');
    Route::delete('users/{user}', [ClientServerController::class, 'removeUser'])->name('users.destroy');
    Route::put('allocations/{allocation}/primary', [ClientServerController::class, 'makePrimary'])->name('allocations.primary');
    Route::put('allocations/{allocation}/notes', [ClientServerController::class, 'updateAllocationNotes'])->name('allocations.notes');
    Route::delete('allocations/{allocation}', [ClientServerController::class, 'destroyAllocation'])->name('allocations.destroy');
});

Route::middleware('auth')->prefix('client/servers/{server}/files')->name('client.servers.files.')->group(function () {
    Route::get('list', [ClientFileController::class, 'list'])->name('list');
    Route::get('contents', [ClientFileController::class, 'contents'])->name('contents');
    Route::get('download', [ClientFileController::class, 'download'])->name('download');
    Route::post('save', [ClientFileController::class, 'save'])->name('save');
    Route::post('upload', [ClientFileController::class, 'upload'])->name('upload');
    Route::post('mkdir', [ClientFileController::class, 'mkdir'])->name('mkdir');
    Route::post('rename', [ClientFileController::class, 'rename'])->name('rename');
    Route::post('delete', [ClientFileController::class, 'delete'])->name('delete');
});
