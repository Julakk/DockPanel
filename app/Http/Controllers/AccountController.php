<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function edit()
    {
        return view('account.edit', ['user' => auth()->user()]);
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (! Hash::check($validated['current_password'], auth()->user()->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Password sekarang salah.',
            ]);
        }

        auth()->user()->update(['password' => bcrypt($validated['password'])]);
        ActivityLog::record('account:password-changed');

        return back()->with('success', 'Password berhasil diupdate.');
    }

    public function updateEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore(auth()->id())],
            'password' => 'required|string',
        ]);

        if (! Hash::check($validated['password'], auth()->user()->password)) {
            throw ValidationException::withMessages([
                'password' => 'Password salah.',
            ]);
        }

        auth()->user()->update(['email' => $validated['email']]);
        ActivityLog::record('account:email-changed', ['new_email' => $validated['email']]);

        return back()->with('success', 'Email berhasil diupdate.');
    }

    /**
     * Tampilin halaman setup 2FA — generate secret baru kalau belum ada,
     * atau tampilin status aktif kalau udah enabled.
     */
    public function twoFactorShow(TwoFactorService $twoFactor)
    {
        $user = auth()->user();

        if (! $user->hasTwoFactorEnabled() && ! $user->two_factor_secret) {
            $user->update(['two_factor_secret' => $twoFactor->generateSecret()]);
            $user->refresh();
        }

        $otpAuthUri = $user->hasTwoFactorEnabled()
            ? null
            : $twoFactor->getOtpAuthUri($user->two_factor_secret, $user->email);

        return view('account.two-factor', [
            'user' => $user,
            'secret' => $user->hasTwoFactorEnabled() ? null : $user->two_factor_secret,
            'otpAuthUri' => $otpAuthUri,
        ]);
    }

    public function twoFactorEnable(Request $request, TwoFactorService $twoFactor)
    {
        $request->validate(['code' => 'required|string']);

        $user = auth()->user();

        if (! $twoFactor->verify($user->two_factor_secret, $request->input('code'))) {
            throw ValidationException::withMessages([
                'code' => 'Kode nggak valid. Cek lagi authenticator app kamu.',
            ]);
        }

        $user->update(['two_factor_enabled_at' => now()]);
        ActivityLog::record('account:2fa-enabled');

        return redirect()->route('account.two-factor.show')->with('success', 'Two-factor authentication aktif.');
    }

    public function twoFactorDisable(Request $request)
    {
        $request->validate(['password' => 'required|string']);

        $user = auth()->user();

        if (! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Password salah.',
            ]);
        }

        $user->update(['two_factor_secret' => null, 'two_factor_enabled_at' => null]);
        ActivityLog::record('account:2fa-disabled');

        return redirect()->route('account.two-factor.show')->with('success', 'Two-factor authentication dimatiin.');
    }

    public function activity()
    {
        $logs = auth()->user()->activityLogs()
            ->with('server')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('account.activity', compact('logs'));
    }
}
