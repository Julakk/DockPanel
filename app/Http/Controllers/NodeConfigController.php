<?php

namespace App\Http\Controllers;

use App\Models\Node;

class NodeConfigController extends Controller
{
    public function show(Node $node)
    {
        $config = [
            'listen_addr' => ':'.$node->daemon_listen,
            'sftp_addr' => ':'.$node->daemon_sftp,
            'auth_token' => $node->daemon_token,
            'docker_socket' => '/var/run/docker.sock',
            'data_directory' => '/var/lib/dockwings/servers',
        ];

        return view('nodes.configuration', [
            'node' => $node,
            'json' => json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ]);
    }
}
