<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesServers;
use Tests\TestCase;

class ServerExpiryTest extends TestCase
{
    use MakesServers, RefreshDatabase;

    public function test_command_suspends_only_expired_servers(): void
    {
        $owner = User::factory()->create();

        $expired = $this->makeServer($owner, ['expires_at' => now()->subDay()]);
        $active = $this->makeServer($owner, ['expires_at' => now()->addDays(5)]);
        $forever = $this->makeServer($owner);

        $this->artisan('servers:suspend-expired')->assertSuccessful();

        $this->assertTrue($expired->fresh()->suspended);
        $this->assertSame('expired', $expired->fresh()->suspension_reason);
        $this->assertFalse($active->fresh()->suspended);
        $this->assertFalse($forever->fresh()->suspended);
    }

    public function test_notify_command_marks_servers_expiring_soon(): void
    {
        config(['mail.default' => 'array']);

        $owner = User::factory()->create();
        $soon = $this->makeServer($owner, ['expires_at' => now()->addDay()]);
        $far = $this->makeServer($owner, ['expires_at' => now()->addDays(30)]);

        $this->artisan('servers:notify-expiring', ['--days' => 3])->assertSuccessful();

        $this->assertNotNull($soon->fresh()->expiry_notified_at);
        $this->assertNull($far->fresh()->expiry_notified_at);
    }

    public function test_admin_can_extend_and_unsuspend_expired_server(): void
    {
        $admin = User::factory()->create(['root_admin' => true]);
        $owner = User::factory()->create();
        $server = $this->makeServer($owner, [
            'expires_at' => now()->subDay(),
            'suspended' => true,
            'suspension_reason' => 'expired',
        ]);

        $this->actingAs($admin)
            ->put("/servers/{$server->id}/expiry", ['extend_days' => 30])
            ->assertRedirect();

        $server->refresh();
        $this->assertFalse($server->suspended);
        $this->assertNull($server->suspension_reason);
        $this->assertTrue($server->expires_at->isFuture());
    }

    public function test_extend_does_not_unsuspend_manual_suspension(): void
    {
        $admin = User::factory()->create(['root_admin' => true]);
        $owner = User::factory()->create();
        $server = $this->makeServer($owner, [
            'expires_at' => now()->subDay(),
            'suspended' => true,
        ]);

        $this->actingAs($admin)
            ->put("/servers/{$server->id}/expiry", ['extend_days' => 30])
            ->assertRedirect();

        $this->assertTrue($server->fresh()->suspended);
    }

    public function test_admin_can_clear_expiry(): void
    {
        $admin = User::factory()->create(['root_admin' => true]);
        $owner = User::factory()->create();
        $server = $this->makeServer($owner, ['expires_at' => now()->addDays(3)]);

        $this->actingAs($admin)
            ->put("/servers/{$server->id}/expiry", ['expires_at' => ''])
            ->assertRedirect();

        $this->assertNull($server->fresh()->expires_at);
    }

    public function test_non_admin_cannot_change_expiry(): void
    {
        $owner = User::factory()->create(['root_admin' => false]);
        $server = $this->makeServer($owner, ['expires_at' => null]);

        $response = $this->actingAs($owner)
            ->put("/servers/{$server->id}/expiry", ['extend_days' => 30]);

        $this->assertContains($response->status(), [302, 403]);
        $this->assertNull($server->fresh()->expires_at);
    }

    public function test_extend_days_is_validated(): void
    {
        $admin = User::factory()->create(['root_admin' => true]);
        $owner = User::factory()->create();
        $server = $this->makeServer($owner);

        $this->actingAs($admin)
            ->put("/servers/{$server->id}/expiry", ['extend_days' => 99999])
            ->assertSessionHasErrors('extend_days');
    }
}
