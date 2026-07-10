<?php

namespace Tests\Feature;

use App\Models\Egg;
use App\Models\Nest;
use App\Models\Node;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function makeNode(): Node
    {
        return Node::create([
            'name' => 'Node Test',
            'fqdn' => 'test.local',
            'scheme' => 'https',
            'memory' => 8192,
            'disk' => 102400,
            'daemon_listen' => 8080,
            'daemon_sftp' => 2022,
            'daemon_token' => bcrypt('token'),
        ]);
    }

    protected function makeEgg(): Egg
    {
        $nest = Nest::create(['name' => 'Minecraft']);

        return Egg::create([
            'nest_id' => $nest->id,
            'name' => 'Vanilla',
            'docker_image' => 'ghcr.io/pterodactyl/yolks:java_17',
            'startup' => 'java -jar {{SERVER_JARFILE}}',
        ]);
    }

    public function test_root_admin_can_create_server(): void
    {
        $admin = User::factory()->create(['root_admin' => true]);
        $owner = User::factory()->create();
        $node = $this->makeNode();
        $egg = $this->makeEgg();

        $response = $this->actingAs($admin)->post('/servers', [
            'name' => 'Server Julak',
            'owner_id' => $owner->id,
            'node_id' => $node->id,
            'egg_id' => $egg->id,
            'memory' => 1024,
            'swap' => 0,
            'disk' => 5120,
            'io' => 500,
            'cpu' => 100,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('servers', ['name' => 'Server Julak']);
    }

    public function test_non_admin_cannot_view_servers_index(): void
    {
        $user = User::factory()->create(['root_admin' => false]);

        $response = $this->actingAs($user)->get('/servers');

        $response->assertStatus(403);
    }

    public function test_provision_fails_gracefully_without_real_wings(): void
    {
        $admin = User::factory()->create(['root_admin' => true]);
        $owner = User::factory()->create();
        $node = $this->makeNode();
        $egg = $this->makeEgg();

        $server = Server::create([
            'name' => 'Server Test',
            'owner_id' => $owner->id,
            'node_id' => $node->id,
            'nest_id' => $egg->nest_id,
            'egg_id' => $egg->id,
            'memory' => 1024,
            'swap' => 0,
            'disk' => 5120,
            'io' => 500,
            'cpu' => 100,
            'image' => $egg->docker_image,
            'startup' => $egg->startup,
            'status' => 'installing',
        ]);

        $response = $this->actingAs($admin)->post("/servers/{$server->id}/provision");

        // Nggak ada Wings beneran, jadi harus gagal graceful (redirect back dengan error), bukan 500
        $response->assertRedirect();
        $response->assertSessionHasErrors('provision');
    }
}
