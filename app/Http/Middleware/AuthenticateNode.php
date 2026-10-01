<?php

namespace App\Http\Middleware;

use App\Models\Node;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentikasi request dari daemon Wings: Bearer token harus sama dengan
 * daemon_token salah satu node. Node yang cocok disimpan di request attribute 'node'.
 */
class AuthenticateNode
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        $node = (is_string($token) && $token !== '')
            ? Node::where('daemon_token', $token)->first()
            : null;

        // hash_equals karena collation MariaDB biasanya case-insensitive
        if (! $node || ! hash_equals((string) $node->daemon_token, $token)) {
            return response()->json(['error' => 'Token node nggak valid.'], 401);
        }

        $request->attributes->set('node', $node);

        return $next($request);
    }
}
