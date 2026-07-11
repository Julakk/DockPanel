<?php

namespace App\Http\Controllers;

use App\Models\Egg;
use App\Models\Node;
use App\Models\Server;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard', [
            'user' => auth()->user(),
            'nodeCount' => Node::count(),
            'serverCount' => Server::count(),
            'eggCount' => Egg::count(),
        ]);
    }
}
