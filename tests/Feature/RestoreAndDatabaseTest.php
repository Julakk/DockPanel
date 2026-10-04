<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Concerns\MakesServers;
use Tests\TestCase;

class RestoreAndDatabaseTest extends TestCase
{
    use MakesServers, RefreshDatabase;

    private function completedBackup($server)
    {
        return $server->backups()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Bagus',
            'status' => 'completed',
            'size' => 100,
            'checksum' => str_repeat('b', 64),
        ]);
    }

    public function test_restore_calls_wings_and_locks_server(): void
    {
        Http::fake(['*' => Http::response(['status' => 'restoring'], 202)]);
        $owner = User::factory()->create();
        $server = $this->makeServer($owner);
        $backup = $this->completedBackup($server);

        $this->actingAs($owner)
            ->post("/client/servers/{$server->id}/backups/{$backup->id}/restore")
            ->assertRedirect();

        $this->assertSame('restoring_backup', $server->fresh()->status);
        Http::assertSent(fn ($r) => $r->method() === 'POST' && str_contains($r->url(), "/backups/{$backup->uuid}/restore"));
    }

    public function test_restore_refused_when_wings_says_server_running(): void
    {
        Http::fake(['*' => Http::response(['error' => 'matikan server dulu sebelum restore backup'], 409)]);
        $owner = User::factory()->create();
        $server = $this->makeServer($owner);
        $backup = $this->completedBackup($server);

        $this->actingAs($owner)
            ->post("/client/servers/{$server->id}/backups/{$backup->id}/restore")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNotSame('restoring_backup', $server->fresh()->status);
    }

    public function test_power_is_blocked_while_restoring(): void
    {
        Http::fake(['*' => Http::response(['status' => 'restoring'], 200)]);
        $owner = User::factory()->create();
        $server = $this->makeServer($owner);
        $server->update(['status' => 'restoring_backup']);

        $this->actingAs($owner)
            ->post("/client/servers/{$server->id}/power", ['action' => 'start'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('restoring_backup', $server->fresh()->status);
    }

    public function test_status_returns_to_offline_when_restore_completed(): void
    {
        Http::fake(['*' => Http::response(['status' => 'completed'], 200)]);
        $owner = User::factory()->create();
        $server = $this->makeServer($owner);
        $server->update(['status' => 'restoring_backup']);

        $this->actingAs($owner)->get("/client/servers/{$server->id}?tab=backups")->assertOk();

        $this->assertSame('offline', $server->fresh()->status);
    }
}
