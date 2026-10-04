<?php

namespace Tests\Feature;

use App\Models\DatabaseHost;
use App\Models\ServerDatabase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesServers;
use Tests\TestCase;

class DatabaseManagementTest extends TestCase
{
    use MakesServers, RefreshDatabase;

    private function host(array $overrides = []): DatabaseHost
    {
        return DatabaseHost::create(array_merge([
            'name' => 'Host',
            'host' => '127.0.0.1',
            'port' => 3306,
            'username' => 'dbadmin',
            'password' => 'secret',
        ], $overrides));
    }

    private function database($server, DatabaseHost $host, string $name = 's1_db'): ServerDatabase
    {
        return ServerDatabase::create([
            'server_id' => $server->id,
            'database_host_id' => $host->id,
            'database' => $name,
            'username' => 'u1_abcdef',
            'password' => 'pw-lama',
        ]);
    }

    public function test_owner_creates_database_on_host_linked_to_node(): void
    {
        $owner = User::factory()->create();
        $server = $this->makeServer($owner);
        $this->host(['name' => 'Global']);
        $linked = $this->host(['name' => 'Linked', 'node_id' => $server->node_id]);

        $this->actingAs($owner)
            ->post("/client/servers/{$server->id}/databases", ['database_name' => 'game', 'remote' => '10.0.0.5'])
            ->assertRedirect();

        $db = ServerDatabase::where('server_id', $server->id)->firstOrFail();
        $this->assertSame($linked->id, $db->database_host_id);
        $this->assertSame("s{$server->uuid_short}_game", $db->database);
        $this->assertStringStartsWith("u{$server->uuid_short}_", $db->username);
        $this->assertSame('10.0.0.5', $db->remote);
        $this->assertNotEmpty((string) $db->password);
    }

    public function test_database_limit_is_enforced(): void
    {
        $owner = User::factory()->create();
        $host = $this->host();
        $server = $this->makeServer($owner, ['database_limit' => 1]);
        $this->database($server, $host);

        $this->actingAs($owner)
            ->post("/client/servers/{$server->id}/databases", ['database_name' => 'dua'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(1, ServerDatabase::where('server_id', $server->id)->count());
    }

    public function test_limit_zero_disables_client_creation(): void
    {
        $owner = User::factory()->create();
        $this->host();
        $server = $this->makeServer($owner, ['database_limit' => 0]);

        $this->actingAs($owner)
            ->post("/client/servers/{$server->id}/databases", ['database_name' => 'game'])
            ->assertSessionHas('error');

        $this->assertSame(0, ServerDatabase::count());
    }

    public function test_creation_without_any_host_shows_error(): void
    {
        $owner = User::factory()->create();
        $server = $this->makeServer($owner);

        $this->actingAs($owner)
            ->post("/client/servers/{$server->id}/databases", ['database_name' => 'game'])
            ->assertSessionHas('error');

        $this->assertSame(0, ServerDatabase::count());
    }

    public function test_stranger_cannot_create_database(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $this->host();
        $server = $this->makeServer($owner);

        $this->actingAs($stranger)
            ->post("/client/servers/{$server->id}/databases", ['database_name' => 'game'])
            ->assertForbidden();
    }

    public function test_rotate_password_changes_it(): void
    {
        $owner = User::factory()->create();
        $server = $this->makeServer($owner);
        $db = $this->database($server, $this->host());

        $this->actingAs($owner)
            ->post("/client/servers/{$server->id}/databases/{$db->id}/password")
            ->assertRedirect();

        $new = (string) $db->fresh()->password;
        $this->assertNotSame('pw-lama', $new);
        $this->assertSame(24, strlen($new));
    }

    public function test_databases_tab_shows_endpoint_and_jdbc(): void
    {
        $owner = User::factory()->create();
        $server = $this->makeServer($owner);
        $db = $this->database($server, $this->host(['public_host' => '172.17.0.1']));

        $this->actingAs($owner)
            ->get("/client/servers/{$server->id}?tab=databases")
            ->assertOk()
            ->assertSee('172.17.0.1:3306')
            ->assertSee('jdbc:mysql://', false);

        $this->assertStringContainsString('@172.17.0.1:3306/s1_db', $db->jdbcUrl());
    }

    public function test_two_databases_get_separate_mysql_users(): void
    {
        $admin = User::factory()->create(['root_admin' => true]);
        $server = $this->makeServer($admin);
        $host = $this->host();

        foreach (['satu', 'dua'] as $name) {
            $this->actingAs($admin)->post("/servers/{$server->id}/databases", [
                'database_host_id' => $host->id,
                'database_name' => $name,
            ])->assertRedirect();
        }

        $users = ServerDatabase::pluck('username')->all();
        $this->assertCount(2, $users);
        $this->assertNotSame($users[0], $users[1]);
    }

    public function test_phpmyadmin_signon_token_is_single_use(): void
    {
        config([
            'app.phpmyadmin_url' => 'https://pma.test',
            'app.phpmyadmin_signon_secret' => 'rahasia',
        ]);
        $owner = User::factory()->create();
        $server = $this->makeServer($owner);
        $db = $this->database($server, $this->host());

        $location = $this->actingAs($owner)
            ->post("/client/servers/{$server->id}/databases/{$db->id}/phpmyadmin")
            ->assertRedirect()
            ->headers->get('Location');

        $this->assertStringStartsWith('https://pma.test/dockpanel-signon.php?token=', $location);
        $token = substr($location, strrpos($location, '=') + 1);

        $this->postJson('/api/pma/redeem', ['token' => $token], ['X-Pma-Secret' => 'salah'])->assertNotFound();

        $this->postJson('/api/pma/redeem', ['token' => $token], ['X-Pma-Secret' => 'rahasia'])
            ->assertOk()
            ->assertJson(['user' => 'u1_abcdef', 'password' => 'pw-lama', 'db' => 's1_db', 'host' => '127.0.0.1', 'port' => 3306]);

        // token udah terpakai
        $this->postJson('/api/pma/redeem', ['token' => $token], ['X-Pma-Secret' => 'rahasia'])->assertNotFound();
    }

    public function test_phpmyadmin_signon_refused_for_other_users_and_when_unconfigured(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $server = $this->makeServer($owner);
        $db = $this->database($server, $this->host());

        // belum dikonfigurasi
        $this->actingAs($owner)
            ->post("/client/servers/{$server->id}/databases/{$db->id}/phpmyadmin")
            ->assertSessionHas('error');

        config(['app.phpmyadmin_url' => 'https://pma.test', 'app.phpmyadmin_signon_secret' => 'rahasia']);
        $this->actingAs($stranger)
            ->post("/client/servers/{$server->id}/databases/{$db->id}/phpmyadmin")
            ->assertForbidden();
    }

    public function test_host_for_server_prefers_linked_node(): void
    {
        $server = $this->makeServer(User::factory()->create());
        $global = $this->host(['name' => 'Global']);
        $this->assertSame($global->id, DatabaseHost::forServer($server)->id);

        $linked = $this->host(['name' => 'Linked', 'node_id' => $server->node_id]);
        $this->assertSame($linked->id, DatabaseHost::forServer($server)->id);
    }

    public function test_host_with_databases_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['root_admin' => true]);
        $server = $this->makeServer($admin);
        $host = $this->host();
        $this->database($server, $host);

        $this->actingAs($admin)->delete("/databases/{$host->id}")->assertSessionHas('error');

        $this->assertDatabaseHas('database_hosts', ['id' => $host->id]);
    }
}
