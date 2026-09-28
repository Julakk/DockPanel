<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Server;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ServerExpiryController extends Controller
{
    public function update(Request $request, Server $server)
    {
        $data = $request->validate([
            'expires_at' => ['nullable', 'date'],
            'extend_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        if (! empty($data['extend_days'])) {
            $base = ($server->expires_at && $server->expires_at->isFuture()) ? $server->expires_at : now();
            $expires = $base->copy()->addDays((int) $data['extend_days']);
        } else {
            $expires = ! empty($data['expires_at']) ? Carbon::parse($data['expires_at']) : null;
        }

        $attrs = ['expires_at' => $expires, 'expiry_notified_at' => null];

        // Buka suspend HANYA kalau penyebabnya expired (bukan suspend manual admin).
        if ($server->suspended && $server->suspension_reason === 'expired' && (! $expires || $expires->isFuture())) {
            $attrs['suspended'] = false;
            $attrs['suspension_reason'] = null;
        }

        $server->forceFill($attrs)->save();

        rescue(fn () => ActivityLog::record('server:expiry-updated', [
            'server' => $server->uuid_short,
            'expires_at' => $expires?->toDateTimeString(),
        ]));

        return back()->with('success', $expires
            ? 'Masa aktif diatur sampai '.$expires->format('d M Y H:i').'.'
            : 'Masa aktif dihapus (server tanpa batas waktu).');
    }
}
