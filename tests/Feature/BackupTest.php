<?php

namespace Tests\Feature;

use App\Models\Allocation;
use App\Models\DatabaseHost;
use App\Models\ServerDatabase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Concerns\MakesServers;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use MakesServers, RefreshDatabase;

    public function test_owner_can_create_backup_and_wings_is_called(): void
    {
        Http::fake(['*' => Http::response(['status' => 'creating'], 202)]);
        $owner = User::factory()->create();
        $server = $this->makeServer($owner);

        $this->actingAs($owner)
            ->post("/client/servers/{$server->id}/backups", ['name' => 'Tes'])
            ->assertRedirect();

        $this->assertDatabaseHas('backups', ['server_id' => $server->id, 'name' => 'Tes', 'status' => 'creating']);
        Http::assertSent(fn ($r) => $r->method() === 'POST' && str_contains($r->url(), "/api/servers/{$server->uuid}/backups"));
    }

    public function test_limit_zero_blocks_backup_creation(): void
    {
        Http::fake();
        $owner = User::factory()->create();
        $server = $this->makeServer($owner, ['backup_limit' => 0]);

        $this->actingAs($owner)->post("/client/servers/{$server->id}/backups")->assertRedirect();

        $this->assertDatabaseCount('backups', 0);
        Http::assertNothingSent();
    }

    public function test_wings_error_marks_backup_failed(): void
    {
        Http::fake(['*' => Http::response(['error' => 'folder data server nggak ketemu'], 422)]);
        $owner = User::factory()->create();
        $server = $this->makeServer($owner);

        $this->actingAs($owner)->post("/client/servers/{$server->id}/backups")->assertRedirect();

        $this->assertDatabaseHas('backups', ['server_id' => $server->id, 'status' => 'failed']);
    }

    public function test_stranger_cannot_manage_backups(): void
    {
        Http::fake();
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $server = $this->makeServer($owner);

        $this->actingAs($stranger)->post("/client/servers/{$server->id}/backups")->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_backups_tab_syncs_creating_backup_from_wings(): void
    {
        Http::fake(['*' => Http::response(['status' => 'completed', 'size' => 2048, 'checksum' => str_repeat('a', 64)], 200)]);
        $owner = User::factory()->create();
        $server = $this->makeServer($owner);
        $backup = $server->backups()->create(['uuid' => (string) Str::uuid(), 'name' => 'Lama', 'status' => 'creating']);

        $this->actingAs($owner)
            ->get("/client/servers/{$server->id}?tab=backups")
            ->assertOk()
            ->assertSee('Lama');

        $this->assertSame('completed', $backup->fresh()->status);
        $this->assertSame(2048, $backup->fresh()->size);
    }

    public function test_make_primary_pushes_allocations_to_wings(): void
    {
        Http::fake(['*' => Http::response(['success' => true, 'applied' => true, 'restarted' => true], 200)]);
        $owner = User::factory()->create();
        $server = $this->makeServer($owner);
        Allocation::create(['node_id' => $server->node_id, 'ip' => '1.1.1.1', 'port' => 1000, 'server_id' => $server->id, 'is_primary' => true]);
        $second = Allocation::create(['node_id' => $server->node_id, 'ip' => '1.1.1.1', 'port' => 1001, 'server_id' => $server->id, 'is_primary' => false]);

        $this->actingAs($owner)
            ->put("/client/servers/{$server->id}/allocations/{$second->id}/primary")
            ->assertRedirect();

        $this->assertTrue($second->fresh()->is_primary);
        Http::assertSent(fn ($r) => $r->method() === 'PUT' && str_contains($r->url(), "/api/servers/{$server->uuid}/allocations"));
    }

    public function test_owner_can_delete_database_from_client_area(): void
    {
        $owner = User::factory()->create();
        $server = $this->makeServer($owner);
        $host = DatabaseHost::create(['name' => 'Host', 'host' => '127.0.0.1', 'port' => 3306, 'username' => 'root', 'password' => 'secret']);
        $db = ServerDatabase::create([
            'server_id' => $server->id,
            'database_host_id' => $host->id,
            'database' => 's1_db',
            'username' => 'u1',
            'password' => 'pw',
        ]);

        $this->actingAs($owner)->get("/client/servers/{$server->id}?tab=databases")->assertOk()->assertSee('Connections from');
        $this->actingAs($owner)->delete("/client/servers/{$server->id}/databases/{$db->id}")->assertRedirect();

        $this->assertDatabaseMissing('server_databases', ['id' => $db->id]);
    }
}
