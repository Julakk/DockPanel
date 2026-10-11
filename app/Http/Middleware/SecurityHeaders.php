<?php

namespace App\Http\Middleware;

use App\Models\Node;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        ];

        if ($request->isSecure()) {
            $headers['Strict-Transport-Security'] = 'max-age=2592000';
        }

        $csp = $this->contentSecurityPolicy($request, $response);

        if ($csp !== null) {
            $headers[$csp['header']] = $csp['value'];
        }

        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }

    /**
     * @return array{header: string, value: string}|null
     */
    private function contentSecurityPolicy(Request $request, Response $response): ?array
    {
        $mode = config('security.csp_mode', 'report-only');
        $mode = in_array($mode, ['off', 'enforce'], true) ? $mode : 'report-only';

        if ($mode === 'off' || ! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return null;
        }

        $directives = [
            "default-src 'self'",
            // View masih pakai script/style inline (<script>, onclick=, style=),
            // tapi semuanya dari origin sendiri: nggak ada sumber eksternal.
            "script-src 'self' 'unsafe-inline'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data:",
            "font-src 'self'",
            'connect-src '.implode(' ', ["'self'", ...$this->consoleOrigins($request)]),
            "object-src 'none'",
            "base-uri 'self'",
            'form-action '.implode(' ', ["'self'", ...$this->formActionOrigins()]),
            "frame-ancestors 'self'",
            'report-uri /api/csp-report',
        ];

        return [
            'header' => $mode === 'enforce' ? 'Content-Security-Policy' : 'Content-Security-Policy-Report-Only',
            'value' => implode('; ', $directives),
        ];
    }

    /**
     * Origin WebSocket console tiap node (ws(s)://fqdn:port), sama persis kayak
     * yang dibentuk JS di halaman server. Di-cache 60 detik. Kalau cache/DB
     * bermasalah, jatuh ke izin per-skema (wss:) biar console nggak ikut mati.
     *
     * @return list<string>
     */
    private function consoleOrigins(Request $request): array
    {
        $proto = $request->isSecure() ? 'wss' : 'ws';

        try {
            $nodes = Cache::remember('csp:console-nodes', 60, function () {
                return Node::query()->get(['fqdn', 'daemon_listen'])
                    ->map(fn (Node $node) => [(string) $node->fqdn, (int) $node->daemon_listen])
                    ->all();
            });
        } catch (Throwable) {
            return ["{$proto}:"];
        }

        $origins = [];

        foreach ($nodes as [$fqdn, $port]) {
            // Hostname dari DB masuk ke header: buang yang aneh (spasi, ';', dst).
            if (preg_match('/^[A-Za-z0-9.-]+$/', $fqdn) && $port > 0) {
                $origins[] = "{$proto}://{$fqdn}:{$port}";
            }
        }

        return array_values(array_unique($origins));
    }

    /**
     * Origin phpMyAdmin (kalau dikonfigurasi): alur signon-nya POST ke panel
     * lalu di-redirect ke sana, dan browser ngecek form-action juga di redirect.
     *
     * @return list<string>
     */
    private function formActionOrigins(): array
    {
        $pma = parse_url((string) config('app.phpmyadmin_url'));

        if (! is_array($pma) || ! in_array($pma['scheme'] ?? null, ['http', 'https'], true)) {
            return [];
        }

        $host = $pma['host'] ?? '';

        if (! preg_match('/^[A-Za-z0-9.-]+$/', $host)) {
            return [];
        }

        $port = isset($pma['port']) ? ':'.(int) $pma['port'] : '';

        return ["{$pma['scheme']}://{$host}{$port}"];
    }
}
