<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServerSubuserController extends Controller
{
    public const AVAILABLE_PERMISSIONS = [
        'control.start' => 'Start server',
        'control.stop' => 'Stop server',
        'control.restart' => 'Restart server',
        'console.access' => 'Akses console',
        'files.read' => 'Lihat file',
        'files.write' => 'Edit/upload file',
        'database.view' => 'Lihat database',
    ];

    public function store(Request $request, Server $server)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'permissions' => 'nullable|array',
            'permissions.*' => Rule::in(array_keys(self::AVAILABLE_PERMISSIONS)),
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ($user->id === $server->owner_id) {
            return back()->withErrors(['email' => 'User ini udah jadi owner server, nggak perlu jadi subuser.']);
        }

        if ($server->subusers()->where('user_id', $user->id)->exists()) {
            return back()->withErrors(['email' => 'User ini udah jadi subuser di server ini.']);
        }

        $server->subusers()->attach($user->id, [
            'permissions' => json_encode($validated['permissions'] ?? []),
        ]);

        return back()->with('success', "{$user->name} ditambahin sebagai subuser.");
    }

    public function destroy(Server $server, User $user)
    {
        $server->subusers()->detach($user->id);

        return back()->with('success', "{$user->name} dicabut dari subuser server ini.");
    }
}
