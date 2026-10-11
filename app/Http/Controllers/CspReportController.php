<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class CspReportController extends Controller
{
    /**
     * Terima laporan pelanggaran CSP dari browser dan catat ke log. Endpoint
     * publik (browser ngirim tanpa kredensial), jadi dibatasi throttle di route
     * dan tiap field dipotong biar log nggak bisa dibanjiri data besar.
     */
    public function __invoke(Request $request): Response
    {
        $report = $request->json('csp-report');
        $report = is_array($report) ? $report : [];

        $clip = fn ($value) => is_scalar($value) ? mb_substr((string) $value, 0, 200) : null;

        Log::notice('csp.violation', [
            'directive' => $clip($report['effective-directive'] ?? $report['violated-directive'] ?? null),
            'blocked' => $clip($report['blocked-uri'] ?? null),
            'page' => $clip($report['document-uri'] ?? null),
            'source' => $clip($report['source-file'] ?? null),
            'line' => $clip($report['line-number'] ?? null),
        ]);

        return response()->noContent();
    }
}
