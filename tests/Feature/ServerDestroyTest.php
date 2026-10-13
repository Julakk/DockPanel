<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesServers;
use Tests\TestCase;

class ServerDestroyTest extends TestCase
{
    use MakesServers, RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['root_admin' => true]);
    }

    public function test_destroy_calls_wings_with_purge_and_removes_row(): void
    {
        Http::fake(['*' => Http::response(['success' => true, 'purged' => true], 200)]);
        $server = $this->makeServer(User::factory()->create());

        $this->actingAs($this->admin())->delete("/servers/{$server->id}", ['purge' => '1'])
            ->assertRedirect(route('servers.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('servers', ['id' => $server->id]);
        $this->assertDatabaseHas('activity_logs', ['event' => 'server:delete']);
        Http::assertSent(fn ($request) => $request->method() === 'DELETE'
            && str_contains($request->url(), "/api/servers/{$server->uuid}")
            && str_contains($request->url(), 'purge=1'));
    }

    public function test_destroy_without_purge_does_not_ask_wings_to_purge(): void
    {
        Http::fake(['*' => Http::response(['success' => true, 'purged' => false], 200)]);
        $server = $this->makeServer(User::factory()->create());

        $this->actingAs($this->admin())->delete("/servers/{$server->id}")->assertRedirect(route('servers.index'));

        $this->assertDatabaseMissing('servers', ['id' => $server->id]);
        Http::assertSent(fn ($request) => ! str_contains($request->url(), 'purge'));
    }

    public function test_unreachable_wings_keeps_the_server(): void
    {
        Http::fake(['*' => fn () => throw new ConnectionException('node mati')]);
        $server = $this->makeServer(User::factory()->create());

        $this->actingAs($this->admin())->delete("/servers/{$server->id}", ['purge' => '1'])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('servers', ['id' => $server->id]);
    }

    public function test_wings_rejection_keeps_the_server(): void
    {
        Http::fake(['*' => Http::response(['error' => 'install sedang berjalan, tunggu selesai'], 409)]);
        $server = $this->makeServer(User::factory()->create());

        $this->actingAs($this->admin())->delete("/servers/{$server->id}")
            ->assertSessionHas('error', fn ($message) => str_contains($message, '409'));

        $this->assertDatabaseHas('servers', ['id' => $server->id]);
    }

    public function test_server_unknown_to_node_is_still_deleted(): void
    {
        Http::fake(['*' => Http::response(['error' => 'server nggak ketemu'], 404)]);
        $server = $this->makeServer(User::factory()->create());

        $this->actingAs($this->admin())->delete("/servers/{$server->id}")->assertSessionHas('success');

        $this->assertDatabaseMissing('servers', ['id' => $server->id]);
    }

    public function test_force_deletes_without_calling_wings(): void
    {
        Http::fake();
        $server = $this->makeServer(User::factory()->create());

        $this->actingAs($this->admin())->delete("/servers/{$server->id}", ['force' => '1'])->assertSessionHas('success');

        $this->assertDatabaseMissing('servers', ['id' => $server->id]);
        Http::assertNothingSent();
    }

    public function test_old_wings_without_purged_key_adds_a_note(): void
    {
        Http::fake(['*' => Http::response(['success' => true], 200)]);
        $server = $this->makeServer(User::factory()->create());

        $this->actingAs($this->admin())->delete("/servers/{$server->id}", ['purge' => '1'])
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'v0.5.4'));
    }
}
