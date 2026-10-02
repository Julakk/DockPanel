<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

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

        $key = $this->throttleKey($request, $credentials['email']);
        $this->ensureNotLockedOut($key, 'email');

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, self::DECAY_SECONDS);
            ActivityLog::record('auth:failed', ['email' => $credentials['email']]);

            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        RateLimiter::clear($key);
        $user = Auth::user();

        // 2FA aktif: logout dulu, simpan id sementara, minta kode TOTP.
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

        $key = $this->throttleKey($request, '2fa:'.$userId);
        $this->ensureNotLockedOut($key, 'code');

        $user = User::findOrFail($userId);

        if (! $twoFactor->verify($user->two_factor_secret, $request->input('code'))) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'code' => 'Kode 2FA salah.',
            ]);
        }

        RateLimiter::clear($key);

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

    private function throttleKey(Request $request, string $identity): string
    {
        return Str::transliterate(Str::lower($identity).'|'.$request->ip());
    }

    private function ensureNotLockedOut(string $key, string $field): void
    {
        if (! RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return;
        }

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            $field => "Kebanyakan percobaan. Coba lagi dalam {$seconds} detik.",
        ]);
    }
}
