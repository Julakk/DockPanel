<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\ServerSchedule;
use Cron\CronExpression;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ServerScheduleController extends Controller
{
    private const MAX_PER_SERVER = 10;

    private function authorizeAccess(Request $request, Server $server): void
    {
        $user = $request->user();

        // Schedule jalan sebagai sistem (bisa kirim command / power), jadi
        // cuma admin & owner. Subuser nggak boleh nembus izin console/control.
        $ok = (bool) ($user->root_admin ?? false)
            || (int) $server->owner_id === (int) $user->id;

        abort_unless($ok, 403);
    }

    private function back(Server $server)
    {
        return redirect()->route('client.servers.show', ['server' => $server, 'tab' => 'schedules']);
    }

    public function store(Request $request, Server $server)
    {
        $this->authorizeAccess($request, $server);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'action' => ['required', 'in:'.implode(',', ServerSchedule::ACTIONS)],
            'payload' => ['nullable', 'string', 'max:255', 'required_if:action,command'],
            'cron' => ['required', 'string', 'max:60'],
        ]);

        $cron = trim(preg_replace('/\s+/', ' ', $data['cron']));

        if (! CronExpression::isValidExpression($cron)) {
            throw ValidationException::withMessages(['cron' => 'Format cron nggak valid. Contoh: */30 * * * *']);
        }

        if ($server->schedules()->count() >= self::MAX_PER_SERVER) {
            throw ValidationException::withMessages(['name' => 'Maksimal '.self::MAX_PER_SERVER.' schedule per server.']);
        }

        $server->schedules()->create([
            'name' => $data['name'],
            'action' => $data['action'],
            'payload' => $data['action'] === 'command' ? $data['payload'] : null,
            'cron' => $cron,
        ]);

        return $this->back($server)->with('success', 'Schedule ditambahkan.');
    }

    public function toggle(Request $request, Server $server, ServerSchedule $schedule)
    {
        $this->authorizeAccess($request, $server);
        abort_unless((int) $schedule->server_id === (int) $server->id, 404);

        $schedule->update(['is_active' => ! $schedule->is_active]);

        return $this->back($server);
    }

    public function destroy(Request $request, Server $server, ServerSchedule $schedule)
    {
        $this->authorizeAccess($request, $server);
        abort_unless((int) $schedule->server_id === (int) $server->id, 404);

        $schedule->delete();

        return $this->back($server)->with('success', 'Schedule dihapus.');
    }
}
