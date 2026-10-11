<?php

namespace Tests\Feature;

use App\Models\Node;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class ContentSecurityPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function node(string $fqdn, int $port = 8080): Node
    {
        return Node::create([
            'name' => 'Node '.$fqdn, 'fqdn' => $fqdn, 'scheme' => 'https',
            'memory' => 1024, 'disk' => 10240,
            'daemon_listen' => $port, 'daemon_sftp' => 2022,
            'daemon_token' => 'token-'.$fqdn,
        ]);
    }

    private function reportOnly(string $url = '/login'): string
    {
        return (string) $this->get($url)->headers->get('Content-Security-Policy-Report-Only');
    }

    public function test_report_only_header_is_the_default(): void
    {
        $response = $this->get('/login');
        $csp = (string) $response->headers->get('Content-Security-Policy-Report-Only');

        $this->assertNull($response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringContainsString('report-uri /api/csp-report', $csp);
    }

    public function test_enforce_mode_sends_the_blocking_header(): void
    {
        config(['security.csp_mode' => 'enforce']);

        $response = $this->get('/login');

        $this->assertNotNull($response->headers->get('Content-Security-Policy'));
        $this->assertNull($response->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_off_mode_sends_no_csp_header(): void
    {
        config(['security.csp_mode' => 'off']);

        $response = $this->get('/login');

        $this->assertNull($response->headers->get('Content-Security-Policy'));
        $this->assertNull($response->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_unknown_mode_falls_back_to_report_only(): void
    {
        config(['security.csp_mode' => 'ngawur']);

        $response = $this->get('/login');

        $this->assertNull($response->headers->get('Content-Security-Policy'));
        $this->assertNotNull($response->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_node_console_origin_follows_the_page_scheme(): void
    {
        $this->node('wings.example.test', 8080);

        $this->assertStringContainsString('ws://wings.example.test:8080', $this->reportOnly('http://localhost/login'));
        $this->assertStringContainsString('wss://wings.example.test:8080', $this->reportOnly('https://localhost/login'));
    }

    public function test_unsafe_node_hostnames_are_not_put_in_the_header(): void
    {
        $this->node('ok.example.test', 8080);
        $this->node('evil.test; script-src *', 8080);

        $csp = $this->reportOnly();

        $this->assertStringContainsString('ok.example.test:8080', $csp);
        $this->assertStringNotContainsString('script-src *', $csp);
        $this->assertStringNotContainsString('evil.test', $csp);
    }

    public function test_phpmyadmin_origin_is_allowed_in_form_action(): void
    {
        config(['app.phpmyadmin_url' => 'https://pma.example.test:8443/index.php']);

        $this->assertStringContainsString("form-action 'self' https://pma.example.test:8443", $this->reportOnly());
    }

    public function test_form_action_stays_self_only_without_phpmyadmin(): void
    {
        config(['app.phpmyadmin_url' => null]);

        $this->assertMatchesRegularExpression("/form-action 'self'(;|$)/", $this->reportOnly());
    }

    public function test_csp_report_endpoint_accepts_browser_reports_and_logs_them(): void
    {
        Log::spy();

        $body = json_encode(['csp-report' => [
            'effective-directive' => 'script-src',
            'blocked-uri' => 'https://evil.test/x.js',
            'document-uri' => 'https://panel.test/dashboard',
        ]]);

        $response = $this->call('POST', '/api/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], $body);

        $response->assertNoContent();
        $this->assertNull($response->headers->get('Content-Security-Policy-Report-Only'));

        Log::shouldHaveReceived('notice')
            ->with('csp.violation', Mockery::on(fn ($context) => $context['blocked'] === 'https://evil.test/x.js'))
            ->once();
    }

    public function test_csp_report_endpoint_survives_garbage_payloads(): void
    {
        $this->call('POST', '/api/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], 'bukan json')
            ->assertNoContent();

        $nested = json_encode(['csp-report' => ['blocked-uri' => ['x'], 'line-number' => ['a' => 1], 'document-uri' => str_repeat('a', 5000)]]);

        $this->call('POST', '/api/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], $nested)
            ->assertNoContent();
    }
}
