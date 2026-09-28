<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;

class AuditLogController extends Controller
{
    public function index()
    {
        $logs = ActivityLog::query()->orderByDesc('id')->simplePaginate(50);

        return view('admin.audit', compact('logs'));
    }
}
