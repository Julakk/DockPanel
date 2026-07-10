<?php

namespace Tests\Feature;

use App\Models\Node;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllocationManagementTest extends TestCase
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

    public function test_root_admin_can_add_allocation_range(): void
    {
        $admin = User::factory()->create(['root_admin' => true]);
        $node = $this->makeNode();

        $response = $this->actingAs($admin)->post("/nodes/{$node->id}/allocations", [
            'ip' => '127.0.0.1',
            'port_start' => 7777,
            'port_end' => 7780,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('allocations', 4);
    }

    public function test_cannot_add_more_than_100_ports_at_once(): void
    {
        $admin = User::factory()->create(['root_admin' => true]);
        $node = $this->makeNode();

        $response = $this->actingAs($admin)->post("/nodes/{$node->id}/allocations", [
            'ip' => '127.0.0.1',
            'port_start' => 1000,
            'port_end' => 2000,
        ]);

        $response->assertSessionHasErrors('port_end');
    }
}
