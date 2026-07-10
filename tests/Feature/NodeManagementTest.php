<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NodeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_admin_can_view_nodes_index(): void
    {
        $admin = User::factory()->create(['root_admin' => true]);

        $response = $this->actingAs($admin)->get('/nodes');

        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_nodes_index(): void
    {
        $user = User::factory()->create(['root_admin' => false]);

        $response = $this->actingAs($user)->get('/nodes');

        $response->assertStatus(403);
    }

    public function test_guest_is_redirected_from_nodes_index(): void
    {
        $response = $this->get('/nodes');

        $response->assertRedirect('/login');
    }
}
