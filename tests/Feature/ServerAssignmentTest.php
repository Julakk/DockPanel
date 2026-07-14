<?php

namespace Tests\Feature;

use App\Models\DatabaseHost;
use App\Models\Egg;
use App\Models\Mount;
use App\Models\Nest;
use App\Models\Node;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['root_admin' => true]);
    }

    protected function makeServer(): Server
    {
        $owner = User::factory()->create();
        $node = Node::create([
            'name' => 'Node Test', 'fqdn' => 'test.local', 'scheme' => 'https',
            'memory' => 8192, 'disk' => 102400,
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

    public function test_admin_can_create_database_for_server(): void
    {
        $server = $this->makeServer();
        $host = DatabaseHost::create([
            'name' => 'Host Utama', 'host' => '127.0.0.1', 'port' => 3306,
            'username' => 'root', 'password' => 'secret',
        ]);

        $response = $this->actingAs($this->admin())->post("/servers/{$server->id}/databases", [
            'database_host_id' => $host->id,
            'database_name' => 'mygame',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('server_databases', ['server_id' => $server->id]);
    }

    public function test_admin_can_assign_mounts_to_server(): void
    {
        $server = $this->makeServer();
        $mount = Mount::create([
            'name' => 'Shared Maps', 'source' => '/var/lib/maps', 'target' => '/mnt/maps',
        ]);

        $response = $this->actingAs($this->admin())->put("/servers/{$server->id}/mounts", [
            'mount_ids' => [$mount->id],
        ]);

        $response->assertRedirect();
        $this->assertTrue($server->fresh()->mounts->contains($mount->id));
    }

    public function test_cannot_create_duplicate_database_name_on_same_host(): void
    {
        $server = $this->makeServer();
        $host = DatabaseHost::create([
            'name' => 'Host Utama', 'host' => '127.0.0.1', 'port' => 3306,
            'username' => 'root', 'password' => 'secret',
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)->post("/servers/{$server->id}/databases", [
            'database_host_id' => $host->id,
            'database_name' => 'mygame',
        ]);

        $response = $this->actingAs($admin)->post("/servers/{$server->id}/databases", [
            'database_host_id' => $host->id,
            'database_name' => 'mygame',
        ]);

        $response->assertSessionHasErrors('database_name');
    }
}
