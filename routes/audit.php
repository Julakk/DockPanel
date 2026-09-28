<?php

use App\Http\Controllers\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'root_admin'])->get('admin/audit', [AuditLogController::class, 'index'])->name('admin.audit');
