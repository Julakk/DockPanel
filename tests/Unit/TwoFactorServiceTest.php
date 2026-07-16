<?php

namespace Tests\Unit;

use App\Services\TwoFactorService;
use Tests\TestCase;

class TwoFactorServiceTest extends TestCase
{
    public function test_generates_valid_base32_secret(): void
    {
        $service = new TwoFactorService;
        $secret = $service->generateSecret();

        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
        $this->assertGreaterThanOrEqual(16, strlen($secret));
    }

    public function test_verify_accepts_currently_valid_code(): void
    {
        $service = new TwoFactorService;
        $secret = $service->generateSecret();

        // Generate kode yang valid buat time-step sekarang lewat reflection,
        // karena generateCode() protected — kita tes lewat method public verify()
        // dengan cara membangkitkan kode yang sama pakai algoritma identik di sini.
        $timeStep = (int) floor(time() / 30);
        $code = $this->referenceTotp($secret, $timeStep);

        $this->assertTrue($service->verify($secret, $code));
    }

    public function test_verify_rejects_wrong_code(): void
    {
        $service = new TwoFactorService;
        $secret = $service->generateSecret();

        $this->assertFalse($service->verify($secret, '000000'));
    }

    public function test_verify_rejects_malformed_code(): void
    {
        $service = new TwoFactorService;
        $secret = $service->generateSecret();

        $this->assertFalse($service->verify($secret, 'abcdef'));
        $this->assertFalse($service->verify($secret, '12345'));
    }

    public function test_otp_auth_uri_contains_expected_parts(): void
    {
        $service = new TwoFactorService;
        $secret = 'JBSWY3DPEHPK3PXP';

        $uri = $service->getOtpAuthUri($secret, 'test@example.com', 'DockPanel');

        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString('secret='.$secret, $uri);
        $this->assertStringContainsString('issuer=DockPanel', $uri);
    }

    /**
     * Implementasi TOTP referensi independen (RFC 6238) buat cross-check
     * hasil TwoFactorService, ditulis ulang di sini biar test nggak
     * "curang" manggil method protected yang sama persis.
     */
    private function referenceTotp(string $secret, int $timeStep): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper($secret);
        $binary = '';

        foreach (str_split($secret) as $char) {
            $pos = strpos($alphabet, $char);
            if ($pos === false) {
                continue;
            }
            $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $key = '';
        foreach (str_split($binary, 8) as $byte) {
            if (strlen($byte) < 8) {
                continue;
            }
            $key .= chr(bindec($byte));
        }

        $data = pack('N*', 0).pack('N*', $timeStep);
        $hash = hash_hmac('sha1', $data, $key, true);
        $offset = ord($hash[19]) & 0x0F;

        $truncated = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        $code = $truncated % 1000000;

        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }
}
