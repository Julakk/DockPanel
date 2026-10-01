<?php

namespace App\Http\Controllers\Remote;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Verifikasi login SFTP buat daemon Wings.
 * Username = "<email>.<uuid_short>", password = password akun Panel.
 * Semua kegagalan dibalas pesan generik yang sama biar nggak bisa dipakai
 * nebak email/server; alasan aslinya cuma masuk log Panel.
 */
class SftpAuthController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $node = $request->attributes->get('node');

        $username = $request->input('username');
        $password = $request->input('password');
        $ip = $request->input('ip');

        if (! is_string($username) || ! is_string($password)
            || $username === '' || $password === ''
            || strlen($username) > 320 || strlen($password) > 1024) {
            return response()->json(['error' => 'Request nggak valid.'], 400);
        }

        $ip = (is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP)) ? $ip : 'unknown';

        $ipKey = "sftp-ip:{$node->id}:{$ip}";
        $userKey = "sftp-user:{$node->id}:".strtolower($username);

        if (RateLimiter::tooManyAttempts($ipKey, 20) || RateLimiter::tooManyAttempts($userKey, 10)) {
            return response()->json(['error' => 'Kebanyakan percobaan login. Coba lagi sebentar.'], 429);
        }

        $fail = function (string $reason) use ($ipKey, $userKey, $node, $ip): JsonResponse {
            RateLimiter::hit($ipKey, 60);
            RateLimiter::hit($userKey, 60);
            // username sengaja nggak dicatat: orang sering salah ketik password di kolom itu
            Log::notice('sftp.auth ditolak', ['reason' => $reason, 'node' => $node->id, 'ip' => $ip]);

            return response()->json(['error' => 'Kredensial nggak valid.'], 401);
        };

        $pos = strrpos($username, '.');
        $email = $pos === false ? '' : substr($username, 0, $pos);
        $short = $pos === false ? '' : strtolower(substr($username, $pos + 1));

        if ($email === '' || ! preg_match('/^[a-f0-9]{8}$/', $short)) {
            Hash::make($password); // samain waktu respons dengan jalur normal

            return $fail('format username');
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            Hash::make($password);

            return $fail('user nggak ada');
        }
        if (! Hash::check($password, $user->password)) {
            return $fail('password salah');
        }

        // Server harus ada DI NODE YANG MANGGIL: token satu node nggak bisa dipakai buat node lain.
        $server = Server::where('uuid_short', $short)->where('node_id', $node->id)->first();

        if (! $server) {
            return $fail('server nggak ada di node ini');
        }
        if ($server->suspended) {
            return $fail('server suspended');
        }

        $isManager = (bool) $user->root_admin || (int) $server->owner_id === (int) $user->id;
        $perms = [];

        if (! $isManager) {
            $sub = $server->subusers()->where('users.id', $user->id)->first();
            if (! $sub) {
                return $fail('bukan subuser');
            }

            $perms = json_decode($sub->pivot->permissions ?? '[]', true);
            $perms = is_array($perms) ? $perms : [];

            if (! in_array('files.read', $perms, true)) {
                return $fail('nggak punya files.read');
            }
        }

        $canWrite = $isManager || in_array('files.write', $perms, true);

        RateLimiter::clear($userKey);

        return response()->json([
            'server' => $server->uuid,
            'read_only' => ! $canWrite,
        ]);
    }
}
