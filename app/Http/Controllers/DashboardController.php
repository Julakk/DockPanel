<?php

namespace App\Http\Controllers;

use App\Models\Server;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->isRootAdmin()) {
            return view('dashboard', ['user' => $user]);
        }

        $servers = Server::with(['node', 'egg'])
            ->where('owner_id', $user->id)
            ->orWhereHas('subusers', fn ($q) => $q->where('users.id', $user->id))
            ->orderBy('name')
            ->get();

        return view('client.servers', compact('user', 'servers'));
    }
}
