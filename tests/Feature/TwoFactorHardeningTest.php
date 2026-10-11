<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class TwoFactorHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function totp(string $secret, ?int $step = null): string
    {
        $method = new ReflectionMethod(TwoFactorService::class, 'generateCode');

        return $method->invoke(new TwoFactorService, $secret, $step ?? (int) floor(time() / 30));
    }

    /**
     * @return array{0: User, 1: string, 2: list<string>}
     */
    private function userWithTwoFactor(): array
    {
        $service = new TwoFactorService;
        $secret = $service->generateSecret();
        $codes = $service->generateRecoveryCodes();

        $user = User::factory()->create();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_enabled_at' => now(),
            'two_factor_recovery_codes' => array_map([$service, 'hashRecoveryCode'], $codes),
        ])->save();

        return [$user, $secret, $codes];
    }

    public function test_totp_code_cannot_be_replayed(): void
    {
        [$user, $secret] = $this->userWithTwoFactor();
        $service = new TwoFactorService;
        $code = $this->totp($secret);

        $this->assertTrue($service->consume($user, $code));
        $this->assertFalse($service->consume($user->fresh(), $code));
    }

    public function test_login_challenge_accepts_totp_and_records_the_step(): void
    {
        [$user, $secret] = $this->userWithTwoFactor();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->post('/login/two-factor', ['code' => $this->totp($secret)])->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->two_factor_last_step);
    }

    public function test_login_challenge_rejects_a_code_that_was_already_used(): void
    {
        [$user, $secret] = $this->userWithTwoFactor();
        $step = (int) floor(time() / 30);
        $user->forceFill(['two_factor_last_step' => $step])->save();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->post('/login/two-factor', ['code' => $this->totp($secret, $step)])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_recovery_code_logs_in_once_and_is_then_consumed(): void
    {
        [$user, , $codes] = $this->userWithTwoFactor();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->post('/login/two-factor', ['code' => strtoupper($codes[0])])->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertCount(7, $user->fresh()->two_factor_recovery_codes);
        $this->assertFalse((new TwoFactorService)->useRecoveryCode($user->fresh(), $codes[0]));
    }

    public function test_wrong_recovery_code_is_rejected(): void
    {
        [$user] = $this->userWithTwoFactor();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->post('/login/two-factor', ['code' => 'zzzzz-zzzzz'])->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertCount(8, $user->fresh()->two_factor_recovery_codes);
    }

    public function test_enabling_two_factor_generates_recovery_codes_stored_as_hashes(): void
    {
        $service = new TwoFactorService;
        $user = User::factory()->create();
        $secret = $service->generateSecret();
        $user->update(['two_factor_secret' => $secret]);

        $response = $this->actingAs($user)->post('/account/two-factor', ['code' => $this->totp($secret)]);

        $response->assertRedirect(route('account.two-factor.show'));
        $response->assertSessionHas('recovery_codes');

        $codes = session('recovery_codes');
        $stored = $user->fresh()->two_factor_recovery_codes;

        $this->assertCount(8, $codes);
        $this->assertCount(8, $stored);
        $this->assertNotContains($codes[0], $stored);
        $this->assertContains($service->hashRecoveryCode($codes[0]), $stored);
    }

    public function test_regenerating_recovery_codes_needs_password_and_invalidates_old_ones(): void
    {
        [$user, , $oldCodes] = $this->userWithTwoFactor();
        $service = new TwoFactorService;

        $this->actingAs($user)
            ->post('/account/two-factor/recovery-codes', ['password' => 'salah-total'])
            ->assertSessionHasErrors('password');
        $this->assertCount(8, $user->fresh()->two_factor_recovery_codes);

        $this->actingAs($user)
            ->post('/account/two-factor/recovery-codes', ['password' => 'password'])
            ->assertSessionHas('recovery_codes');

        $newCodes = session('recovery_codes');

        $this->assertFalse($service->useRecoveryCode($user->fresh(), $oldCodes[0]));
        $this->assertTrue($service->useRecoveryCode($user->fresh(), $newCodes[0]));
    }

    public function test_disabling_two_factor_clears_recovery_codes_and_replay_guard(): void
    {
        [$user] = $this->userWithTwoFactor();
        $user->forceFill(['two_factor_last_step' => 123])->save();

        $this->actingAs($user)
            ->delete('/account/two-factor', ['password' => 'password'])
            ->assertRedirect(route('account.two-factor.show'));

        $fresh = $user->fresh();
        $this->assertNull($fresh->two_factor_secret);
        $this->assertNull($fresh->two_factor_recovery_codes);
        $this->assertNull($fresh->two_factor_last_step);
    }

    public function test_two_factor_page_renders_recovery_status_and_flashed_codes(): void
    {
        [$user] = $this->userWithTwoFactor();

        $this->actingAs($user)
            ->get('/account/two-factor')
            ->assertOk()
            ->assertSee('Recovery code tersisa: 8');

        $this->actingAs($user)
            ->withSession(['recovery_codes' => ['abcde-fghjk']])
            ->get('/account/two-factor')
            ->assertOk()
            ->assertSee('abcde-fghjk')
            ->assertSee('Simpan recovery code ini sekarang');
    }

    public function test_challenge_page_mentions_recovery_codes(): void
    {
        [$user] = $this->userWithTwoFactor();

        $this->withSession(['2fa:user_id' => $user->id])
            ->get('/login/two-factor')
            ->assertOk()
            ->assertSee('recovery code');
    }
}
