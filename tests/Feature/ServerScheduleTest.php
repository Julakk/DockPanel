<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesServers;
use Tests\TestCase;

class ServerScheduleTest extends TestCase
{
    use MakesServers, RefreshDatabase;

    public function test_owner_can_add_schedule(): void
    {
        $owner = User::factory()->create();
        $server = $this->makeServer($owner);

        $this->actingAs($owner)->post("/client/servers/{$server->id}/schedules", [
            'name' => 'Restart harian', 'action' => 'restart', 'cron' => '0 4 * * *',
        ])->assertRedirect();

        $this->assertDatabaseHas('server_schedules', ['server_id' => $server->id, 'cron' => '0 4 * * *']);
    }

    public function test_invalid_cron_is_rejected(): void
    {
        $owner = User::factory()->create();
        $server = $this->makeServer($owner);

        $this->actingAs($owner)->post("/client/servers/{$server->id}/schedules", [
            'name' => 'Ngawur', 'action' => 'restart', 'cron' => 'bukan cron',
        ])->assertSessionHasErrors('cron');
    }

    public function test_command_action_requires_payload(): void
    {
        $owner = User::factory()->create();
        $server = $this->makeServer($owner);

        $this->actingAs($owner)->post("/client/servers/{$server->id}/schedules", [
            'name' => 'Say', 'action' => 'command', 'cron' => '* * * * *',
        ])->assertSessionHasErrors('payload');
    }

    public function test_stranger_cannot_add_schedule(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $server = $this->makeServer($owner);

        $this->actingAs($stranger)->post("/client/servers/{$server->id}/schedules", [
            'name' => 'X', 'action' => 'restart', 'cron' => '* * * * *',
        ])->assertForbidden();
    }

    public function test_due_schedule_is_run_and_recorded(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        $owner = User::factory()->create();
        $server = $this->makeServer($owner);
        $s = $server->schedules()->create([
            'name' => 'Tiap menit', 'action' => 'restart', 'cron' => '* * * * *',
        ]);

        $this->artisan('servers:run-schedules')->assertSuccessful();

        $s->refresh();
        $this->assertNotNull($s->last_run_at);
        $this->assertSame('ok', $s->last_status);
    }

    public function test_suspended_server_schedule_is_skipped(): void
    {
        Http::fake();

        $owner = User::factory()->create();
        $server = $this->makeServer($owner, ['suspended' => true]);
        $s = $server->schedules()->create([
            'name' => 'Tiap menit', 'action' => 'restart', 'cron' => '* * * * *',
        ]);

        $this->artisan('servers:run-schedules')->assertSuccessful();

        $this->assertStringContainsString('skipped', (string) $s->fresh()->last_status);
        Http::assertNothingSent();
    }

    public function test_inactive_schedule_is_not_run(): void
    {
        Http::fake();

        $owner = User::factory()->create();
        $server = $this->makeServer($owner);
        $s = $server->schedules()->create([
            'name' => 'Off', 'action' => 'restart', 'cron' => '* * * * *', 'is_active' => false,
        ]);

        $this->artisan('servers:run-schedules')->assertSuccessful();

        $this->assertNull($s->fresh()->last_run_at);
    }

    public function test_admin_audit_page_is_admin_only(): void
    {
        $user = User::factory()->create(['root_admin' => false]);
        $admin = User::factory()->create(['root_admin' => true]);

        $this->actingAs($user)->get('/admin/audit')->assertStatus(403);
        $this->actingAs($admin)->get('/admin/audit')->assertOk();
    }
}
