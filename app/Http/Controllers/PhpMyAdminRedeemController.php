<?php

namespace App\Http\Controllers;

use App\Services\PhpMyAdminSignon;
use Illuminate\Http\Request;

/**
 * Dipanggil skrip signon phpMyAdmin (server ke server) buat nuker token jadi
 * kredensial. Dijaga secret bersama; tanpa secret yang cocok, jawabannya 404.
 */
class PhpMyAdminRedeemController extends Controller
{
    public function __invoke(Request $request, PhpMyAdminSignon $signon)
    {
        $secret = (string) config('app.phpmyadmin_signon_secret');

        if ($secret === '' || ! hash_equals($secret, (string) $request->header('X-Pma-Secret'))) {
            abort(404);
        }

        $creds = $signon->redeem((string) $request->input('token'));
        abort_unless($creds, 404);

        return response()->json($creds)->header('Cache-Control', 'no-store');
    }
}
