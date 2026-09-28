<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Egg;
use App\Models\Node;
use App\Models\Server;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->isRootAdmin()) {
            return view('dashboard', [
                'user' => $user,
                'nodeCount' => Node::count(),
                'serverCount' => Server::count(),
                'eggCount' => Egg::count(),
                'userCount' => User::count(),
                'runningCount' => Server::where('status', 'running')->where('suspended', false)->count(),
                'installingCount' => Server::where('status', 'installing')->count(),
                'suspendedCount' => Server::where('suspended', true)->count(),
                'expiringCount' => Server::whereNotNull('expires_at')
                    ->where('expires_at', '<=', now()->addDays(7))->count(),
                'nodes' => Node::withCount('servers')->orderBy('name')->get(),
                'recentServers' => Server::with(['owner', 'node'])->latest()->limit(5)->get(),
                'recentActivity' => ActivityLog::with(['user', 'server'])->latest()->limit(8)->get(),
            ]);
        }

        $servers = Server::with(['node', 'egg'])
            ->where('owner_id', $user->id)
            ->orWhereHas('subusers', fn ($q) => $q->where('users.id', $user->id))
            ->orderBy('name')
            ->get();

        return view('client.servers', compact('user', 'servers'));
    }
}
