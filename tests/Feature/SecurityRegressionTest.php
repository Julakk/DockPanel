<?php

namespace Tests\Feature;

use App\Models\Node;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\MakesServers;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    use MakesServers, RefreshDatabase;

    private function subuser(Server $server, array $perms): User
    {
        $sub = User::factory()->create();
        $server->subusers()->attach($sub->id, ['permissions' => json_encode($perms)]);

        return $sub;
    }

    private function nodeWithToken(string $token): Node
    {
        return Node::create([
            'name' => 'Node Token', 'fqdn' => 'token.local', 'scheme' => 'https',
            'memory' => 1024, 'disk' => 10240,
            'daemon_listen' => 8080, 'daemon_sftp' => 2022,
            'daemon_token' => $token,
        ]);
    }

    public function test_console_token_needs_console_access(): void
    {
        $server = $this->makeServer(User::factory()->create());
        $sub = $this->subuser($server, ['files.read']);

        $this->actingAs($sub)
            ->getJson("/client/servers/{$server->id}/console-token")
            ->assertForbidden();
    }

    public function test_console_token_works_with_console_access(): void
    {
        $server = $this->makeServer(User::factory()->create());
        $sub = $this->subuser($server, ['console.access']);

        $this->actingAs($sub)
            ->getJson("/client/servers/{$server->id}/console-token")
            ->assertOk()
            ->assertJsonStructure(['token', 'ws_host', 'ws_port']);
    }

    public function test_subuser_cannot_create_schedule_even_with_all_permissions(): void
    {
        $server = $this->makeServer(User::factory()->create());
        $sub = $this->subuser($server, [
            'control.start', 'control.stop', 'control.restart',
            'console.access', 'files.read', 'files.write', 'database.view',
        ]);

        $this->actingAs($sub)->post("/client/servers/{$server->id}/schedules", [
            'name' => 'x', 'action' => 'command', 'payload' => 'stop', 'cron' => '* * * * *',
        ])->assertForbidden();

        $this->assertSame(0, $server->schedules()->count());
    }

    public function test_daemon_token_is_encrypted_at_rest_and_hash_is_set(): void
    {
        $node = $this->nodeWithToken('plain-secret-token');

        $raw = DB::table('nodes')->where('id', $node->id)->value('daemon_token');
        $hash = DB::table('nodes')->where('id', $node->id)->value('daemon_token_hash');

        $this->assertNotSame('plain-secret-token', $raw);
        $this->assertSame('plain-secret-token', $node->fresh()->daemon_token);
        $this->assertSame(hash('sha256', 'plain-secret-token'), $hash);
    }

    public function test_changing_daemon_token_updates_the_hash(): void
    {
        $node = $this->nodeWithToken('first-token');
        $node->update(['daemon_token' => 'second-token']);

        $this->assertSame(
            hash('sha256', 'second-token'),
            DB::table('nodes')->where('id', $node->id)->value('daemon_token_hash')
        );
    }

    public function test_node_auth_middleware_accepts_only_the_right_token(): void
    {
        $this->nodeWithToken('right-token-123');

        $this->postJson('/api/remote/sftp/auth', [], ['Authorization' => 'Bearer wrong-token'])
            ->assertStatus(401);

        $this->postJson('/api/remote/sftp/auth', [], ['Authorization' => 'Bearer right-token-123'])
            ->assertStatus(400);
    }
}
