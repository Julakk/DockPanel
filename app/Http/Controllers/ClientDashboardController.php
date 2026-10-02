<?php

namespace App\Http\Controllers;

use App\Models\Server;
use Illuminate\Http\Request;

class ClientDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->isRootAdmin();

        // Admin bisa pilih: server miliknya (mine), punya orang lain (others), atau semua (all)
        $filter = $isAdmin ? $request->query('show', 'mine') : 'mine';
        if (! in_array($filter, ['mine', 'others', 'all'], true)) {
            $filter = 'mine';
        }

        $query = Server::with(['node', 'egg', 'primaryAllocation'])->orderBy('name');

        if ($filter === 'mine') {
            $query->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('subusers', fn ($s) => $s->where('users.id', $user->id));
            });
        } elseif ($filter === 'others') {
            $query->where('owner_id', '!=', $user->id)
                ->whereDoesntHave('subusers', fn ($s) => $s->where('users.id', $user->id));
        }

        $servers = $query->limit(300)->get();

        return view('client.servers', compact('user', 'servers', 'filter', 'isAdmin'));
    }
}
