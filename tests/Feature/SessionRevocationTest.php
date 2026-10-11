<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SessionRevocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_other_sessions_are_logged_out_after_password_change(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);
        $oldHash = $user->password;

        $this->actingAs($user)
            ->put('/account/password', [
                'current_password' => 'password123',
                'password' => 'newpassword456',
                'password_confirmation' => 'newpassword456',
            ])
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('newpassword456', $user->fresh()->password));

        // "Device lain": sesi yang masih nyimpen hash password lama.
        $this->actingAs($user->fresh())
            ->withSession(['password_hash_web' => $oldHash])
            ->get('/dashboard')
            ->assertRedirect('/login');
    }

    public function test_session_without_recorded_hash_keeps_working(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }
}
