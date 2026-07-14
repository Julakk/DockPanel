<?php

namespace Tests\Feature;

use App\Models\Egg;
use App\Models\Nest;
use App\Models\Node;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ClientAreaTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_sees_own_servers_on_dashboard(): void
    {
        $user = User::factory()->create(['root_admin' => false]);

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
        Server::create([
            'name' => 'Server Punyaku', 'owner_id' => $user->id, 'node_id' => $node->id,
            'nest_id' => $nest->id, 'egg_id' => $egg->id,
            'memory' => 1024, 'swap' => 0, 'disk' => 5120, 'io' => 500, 'cpu' => 100,
            'image' => $egg->docker_image, 'startup' => $egg->startup, 'status' => 'running',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Server Punyaku');
    }

    public function test_regular_user_still_blocked_from_admin_pages(): void
    {
        $user = User::factory()->create(['root_admin' => false]);

        $response = $this->actingAs($user)->get('/nodes');

        $response->assertStatus(403);
    }

    public function test_user_can_update_own_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('oldpassword')]);

        $response = $this->actingAs($user)->put('/account/password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect();
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_user_cannot_update_password_with_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('oldpassword')]);

        $response = $this->actingAs($user)->put('/account/password', [
            'current_password' => 'salahpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_user_can_create_own_api_credential(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/account/api-credentials', [
            'name' => 'My Script',
        ]);

        $response->assertRedirect(route('account.api-credentials.index'));
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'My Script']);
    }
}
