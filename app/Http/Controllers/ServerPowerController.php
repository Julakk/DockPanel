<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Services\WingsService;
use Illuminate\Http\Request;

class ServerPowerController extends Controller
{
    public function __invoke(Request $request, Server $server)
    {
        $request->validate([
            'action' => 'required|in:start,stop,restart,kill',
        ]);

        $this->authorize('control', $server); // sesuaikan sama Policy yang lo bikin

        $wings = new WingsService($server);
        $ok = $wings->power($request->input('action'));

        return response()->json([
            'success' => $ok,
        ], $ok ? 200 : 502);
    }
}
