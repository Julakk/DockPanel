<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Mount;
use App\Models\Node;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['root_admin' => true]);
    }

    public function test_admin_can_create_user(): void
    {
        $response = $this->actingAs($this->admin())->post('/users', [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['email' => 'testuser@example.com']);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->delete("/users/{$admin->id}");

        $response->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_create_location(): void
    {
        $response = $this->actingAs($this->admin())->post('/locations', [
            'short_code' => 'jakarta',
            'description' => 'Data center Jakarta',
        ]);

        $response->assertRedirect(route('locations.index'));
        $this->assertDatabaseHas('locations', ['short_code' => 'jakarta']);
    }

    public function test_admin_cannot_delete_location_with_nodes(): void
    {
        $location = Location::create(['short_code' => 'jakarta']);
        Node::create([
            'name' => 'Node Test', 'fqdn' => 'test.local', 'scheme' => 'https',
            'location_id' => $location->id,
            'memory' => 1024, 'disk' => 10240,
            'daemon_listen' => 8080, 'daemon_sftp' => 2022,
            'daemon_token' => bcrypt('token'),
        ]);

        $response = $this->actingAs($this->admin())->delete("/locations/{$location->id}");

        $response->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('locations', ['id' => $location->id]);
    }

    public function test_admin_can_update_settings(): void
    {
        $response = $this->actingAs($this->admin())->put('/settings', [
            'company_name' => 'Ahmad Store',
            'require_2fa' => 'admin_only',
            'default_language' => 'id',
        ]);

        $response->assertRedirect(route('settings.edit'));
        $this->assertDatabaseHas('panel_settings', ['company_name' => 'Ahmad Store']);
    }

    public function test_admin_can_create_api_token(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post('/api-keys', [
            'name' => 'Deployment Script',
        ]);

        $response->assertRedirect(route('api-keys.index'));
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'Deployment Script']);
    }

    public function test_admin_can_create_database_host(): void
    {
        $response = $this->actingAs($this->admin())->post('/databases', [
            'name' => 'Database Utama',
            'host' => '127.0.0.1',
            'port' => 3306,
            'username' => 'dockpanel',
            'password' => 'secret',
        ]);

        $response->assertRedirect(route('databases.index'));
        $this->assertDatabaseHas('database_hosts', ['name' => 'Database Utama']);
    }

    public function test_admin_can_create_mount_with_nodes(): void
    {
        $node = Node::create([
            'name' => 'Node Test', 'fqdn' => 'test.local', 'scheme' => 'https',
            'memory' => 1024, 'disk' => 10240,
            'daemon_listen' => 8080, 'daemon_sftp' => 2022,
            'daemon_token' => bcrypt('token'),
        ]);

        $response = $this->actingAs($this->admin())->post('/mounts', [
            'name' => 'Shared Maps',
            'source' => '/var/lib/maps',
            'target' => '/mnt/maps',
            'node_ids' => [$node->id],
        ]);

        $response->assertRedirect(route('mounts.index'));
        $mount = Mount::where('name', 'Shared Maps')->first();
        $this->assertNotNull($mount);
        $this->assertTrue($mount->nodes->contains($node->id));
    }
}
