<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', ['email' => $user->email]);

        $response->assertSessionHasNoErrors();
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create();

        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_user_can_enable_two_factor_with_valid_code(): void
    {
        $user = User::factory()->create();
        $twoFactor = new TwoFactorService;
        $secret = $twoFactor->generateSecret();
        $user->update(['two_factor_secret' => $secret]);

        $timeStep = (int) floor(time() / 30);
        $code = $this->generateTotpForTest($secret, $timeStep);

        $response = $this->actingAs($user)->post('/account/two-factor', ['code' => $code]);

        $response->assertRedirect(route('account.two-factor.show'));
        $this->assertNotNull($user->fresh()->two_factor_enabled_at);
    }

    public function test_login_requires_two_factor_code_when_enabled(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);
        $twoFactor = new TwoFactorService;
        $secret = $twoFactor->generateSecret();
        $user->update(['two_factor_secret' => $secret, 'two_factor_enabled_at' => now()]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('login.two-factor'));
        $this->assertGuest();
    }

    public function test_activity_log_recorded_on_successful_login(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'event' => 'auth:success',
        ]);
    }

    private function generateTotpForTest(string $secret, int $timeStep): string
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
