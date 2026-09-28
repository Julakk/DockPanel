<?php

namespace Tests\Concerns;

use App\Models\Egg;
use App\Models\Nest;
use App\Models\Node;
use App\Models\Server;
use App\Models\User;

trait MakesServers
{
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

    protected function makeEgg(): Egg
    {
        $nest = Nest::create(['name' => 'Minecraft '.uniqid()]);

        return Egg::create([
            'nest_id' => $nest->id,
            'name' => 'Vanilla',
            'docker_image' => 'ghcr.io/pterodactyl/yolks:java_17',
            'startup' => 'java -jar {{SERVER_JARFILE}}',
        ]);
    }

    protected function makeServer(User $owner, array $overrides = []): Server
    {
        $node = $this->makeNode();
        $egg = $this->makeEgg();

        return Server::create(array_merge([
            'name' => 'Server Test',
            'owner_id' => $owner->id,
            'node_id' => $node->id,
            'nest_id' => $egg->nest_id,
            'egg_id' => $egg->id,
            'memory' => 1024,
            'swap' => 0,
            'disk' => 5120,
            'io' => 500,
            'cpu' => 100,
            'startup' => $egg->startup,
            'image' => $egg->docker_image,
        ], $overrides));
    }
}
