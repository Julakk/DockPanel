<?php

namespace Tests\Feature;

use App\Models\Egg;
use App\Models\Nest;
use App\Models\Node;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubuserTest extends TestCase
{
    use RefreshDatabase;

    protected function makeServer(): Server
    {
        $owner = User::factory()->create();
        $node = Node::create([
            'name' => 'Node Test', 'fqdn' => 'test.local', 'scheme' => 'https',
            'memory' => 1024, 'disk' => 10240,
            'daemon_listen' => 8080, 'daemon_sftp' => 2022,
            'daemon_token' => bcrypt('token'),
        ]);
        $nest = Nest::create(['name' => 'Minecraft']);
        $egg = Egg::create([
            'nest_id' => $nest->id, 'name' => 'Vanilla',
            'docker_image' => 'ghcr.io/pterodactyl/yolks:java_17',
            'startup' => 'java -jar server.jar',
        ]);

        return Server::create([
            'name' => 'Server Test', 'owner_id' => $owner->id, 'node_id' => $node->id,
            'nest_id' => $nest->id, 'egg_id' => $egg->id,
            'memory' => 1024, 'swap' => 0, 'disk' => 5120, 'io' => 500, 'cpu' => 100,
            'image' => $egg->docker_image, 'startup' => $egg->startup, 'status' => 'installing',
        ]);
    }

    public function test_admin_can_add_subuser_to_server(): void
    {
        $admin = User::factory()->create(['root_admin' => true]);
        $server = $this->makeServer();
        $subuser = User::factory()->create();

        $response = $this->actingAs($admin)->post("/servers/{$server->id}/subusers", [
            'email' => $subuser->email,
            'permissions' => ['control.start', 'console.access'],
        ]);

        $response->assertRedirect();
        $this->assertTrue($server->fresh()->subusers->contains($subuser->id));
    }

    public function test_cannot_add_owner_as_subuser(): void
    {
        $admin = User::factory()->create(['root_admin' => true]);
        $server = $this->makeServer();
        $owner = $server->owner;

        $response = $this->actingAs($admin)->post("/servers/{$server->id}/subusers", [
            'email' => $owner->email,
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_admin_can_remove_subuser(): void
    {
        $admin = User::factory()->create(['root_admin' => true]);
        $server = $this->makeServer();
        $subuser = User::factory()->create();
        $server->subusers()->attach($subuser->id, ['permissions' => json_encode([])]);

        $response = $this->actingAs($admin)->delete("/servers/{$server->id}/subusers/{$subuser->id}");

        $response->assertRedirect();
        $this->assertFalse($server->fresh()->subusers->contains($subuser->id));
    }
}
