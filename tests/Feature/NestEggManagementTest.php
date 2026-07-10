<?php

namespace Tests\Feature;

use App\Models\Egg;
use App\Models\Nest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NestEggManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_admin_can_view_nests_index(): void
    {
        $admin = User::factory()->create(['root_admin' => true]);

        $response = $this->actingAs($admin)->get('/nests');

        $response->assertStatus(200);
    }

    public function test_root_admin_can_create_nest(): void
    {
        $admin = User::factory()->create(['root_admin' => true]);

        $response = $this->actingAs($admin)->post('/nests', [
            'name' => 'Minecraft',
            'description' => 'Server Minecraft Java & Bedrock',
        ]);

        $response->assertRedirect('/nests');
        $this->assertDatabaseHas('nests', ['name' => 'Minecraft']);
    }

    public function test_root_admin_can_create_egg_with_variables(): void
    {
        $admin = User::factory()->create(['root_admin' => true]);
        $nest = Nest::create(['name' => 'Minecraft']);

        $response = $this->actingAs($admin)->post('/eggs', [
            'nest_id' => $nest->id,
            'name' => 'Vanilla Minecraft',
            'docker_image' => 'ghcr.io/pterodactyl/yolks:java_17',
            'startup' => 'java -jar {{SERVER_JARFILE}}',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('eggs', ['name' => 'Vanilla Minecraft']);

        $egg = Egg::where('name', 'Vanilla Minecraft')->first();

        $varResponse = $this->actingAs($admin)->post("/eggs/{$egg->id}/variables", [
            'name' => 'Server Jar File',
            'env_variable' => 'SERVER_JARFILE',
            'default_value' => 'server.jar',
            'rules' => 'required|string',
        ]);

        $varResponse->assertRedirect();
        $this->assertDatabaseHas('egg_variables', ['env_variable' => 'SERVER_JARFILE']);
    }

    public function test_non_admin_cannot_view_eggs_index(): void
    {
        $user = User::factory()->create(['root_admin' => false]);

        $response = $this->actingAs($user)->get('/eggs');

        $response->assertStatus(403);
    }
}
