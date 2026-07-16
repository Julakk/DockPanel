<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            ActivityLog::record('auth:failed', ['email' => $credentials['email']]);

            throw ValidationException::withMessages([
                'email' => 'Email atau password salah, bre.',
            ]);
        }

        $user = Auth::user();

        // Kalau 2FA aktif, jangan langsung login penuh — logout dulu,
        // simpan id user di session sementara, minta kode TOTP dulu.
        if ($user->hasTwoFactorEnabled()) {
            Auth::logout();
            $request->session()->put('2fa:user_id', $user->id);
            $request->session()->put('2fa:remember', $request->boolean('remember'));

            return redirect()->route('login.two-factor');
        }

        $request->session()->regenerate();
        ActivityLog::record('auth:success');

        return redirect()->intended('/dashboard');
    }

    public function showTwoFactorChallenge(Request $request)
    {
        if (! $request->session()->has('2fa:user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function verifyTwoFactorChallenge(Request $request, TwoFactorService $twoFactor)
    {
        $request->validate(['code' => 'required|string']);

        $userId = $request->session()->get('2fa:user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::findOrFail($userId);

        if (! $twoFactor->verify($user->two_factor_secret, $request->input('code'))) {
            throw ValidationException::withMessages([
                'code' => 'Kode 2FA salah.',
            ]);
        }

        $remember = $request->session()->get('2fa:remember', false);
        $request->session()->forget(['2fa:user_id', '2fa:remember']);

        Auth::login($user, $remember);
        $request->session()->regenerate();
        ActivityLog::record('auth:success');

        return redirect()->intended('/dashboard');
    }

    public function destroy(Request $request)
    {
        ActivityLog::record('auth:logout');

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
