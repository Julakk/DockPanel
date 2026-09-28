<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesServers;
use Tests\TestCase;

class ClientServerTest extends TestCase
{
    use MakesServers, RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $owner = User::factory()->create();
        $server = $this->makeServer($owner);

        $this->get("/client/servers/{$server->id}")->assertRedirect('/login');
    }

    public function test_owner_can_view_server_page_and_every_tab(): void
    {
        $owner = User::factory()->create();
        $server = $this->makeServer($owner);

        foreach (['console', 'files', 'settings', 'startup'] as $tab) {
            $this->actingAs($owner)
                ->get("/client/servers/{$server->id}?tab={$tab}")
                ->assertOk()
                ->assertSee($server->name);
        }
    }

    public function test_unknown_tab_falls_back_to_console(): void
    {
        $owner = User::factory()->create();
        $server = $this->makeServer($owner);

        $this->actingAs($owner)
            ->get("/client/servers/{$server->id}?tab=ngawur")
            ->assertOk();
    }

    public function test_other_user_gets_403(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $server = $this->makeServer($owner);

        $this->actingAs($stranger)->get("/client/servers/{$server->id}")->assertForbidden();
        $this->actingAs($stranger)->getJson("/client/servers/{$server->id}/resources")->assertForbidden();
        $this->actingAs($stranger)->post("/client/servers/{$server->id}/power", ['action' => 'start'])->assertForbidden();
        $this->actingAs($stranger)->post("/client/servers/{$server->id}/command", ['command' => 'say hi'])->assertForbidden();
    }

    public function test_subuser_can_view_server(): void
    {
        $owner = User::factory()->create();
        $sub = User::factory()->create();
        $server = $this->makeServer($owner);
        $server->subusers()->attach($sub->id, ['permissions' => json_encode([])]);

        $this->actingAs($sub)->get("/client/servers/{$server->id}")->assertOk();
    }

    public function test_root_admin_can_view_any_server(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['root_admin' => true]);
        $server = $this->makeServer($owner);

        $this->actingAs($admin)->get("/client/servers/{$server->id}")->assertOk();
    }

    public function test_resources_falls_back_to_mock_when_wings_is_down(): void
    {
        Http::fake(['*' => Http::response([], 500)]);

        $owner = User::factory()->create();
        $server = $this->makeServer($owner, ['memory' => 1024]);

        $this->actingAs($owner)
            ->getJson("/client/servers/{$server->id}/resources")
            ->assertOk()
            ->assertJson([
                'source' => 'mock',
                'cpu_percent' => 0,
                'memory_limit_bytes' => 1024 * 1024 * 1024,
            ]);
    }

    public function test_resources_reads_wings_when_available(): void
    {
        Http::fake(['*' => Http::response([
            'current_state' => 'running',
            'utilization' => ['cpu_absolute' => 12.34, 'memory_bytes' => 2048, 'disk_bytes' => 4096],
        ], 200)]);

        $owner = User::factory()->create();
        $server = $this->makeServer($owner);

        $this->actingAs($owner)
            ->getJson("/client/servers/{$server->id}/resources")
            ->assertOk()
            ->assertJson(['source' => 'wings', 'state' => 'running', 'cpu_percent' => 12.3]);
    }

    public function test_power_action_is_sent_to_wings(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        $owner = User::factory()->create();
        $server = $this->makeServer($owner);

        $this->actingAs($owner)
            ->post("/client/servers/{$server->id}/power", ['action' => 'restart'])
            ->assertSessionHas('success');
    }

    public function test_power_action_rejects_invalid_action(): void
    {
        Http::fake();

        $owner = User::factory()->create();
        $server = $this->makeServer($owner);

        $this->actingAs($owner)
            ->post("/client/servers/{$server->id}/power", ['action' => 'hancurkan'])
            ->assertSessionHasErrors('action');
    }

    public function test_power_and_command_are_blocked_when_suspended(): void
    {
        Http::fake();

        $owner = User::factory()->create();
        $server = $this->makeServer($owner, ['suspended' => true]);

        $this->actingAs($owner)
            ->post("/client/servers/{$server->id}/power", ['action' => 'start'])
            ->assertSessionHas('error');

        $this->actingAs($owner)
            ->post("/client/servers/{$server->id}/command", ['command' => 'say hi'])
            ->assertSessionHas('error');

        Http::assertNothingSent();
    }
}
