<?php

namespace App\Http\Controllers;

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

        return back()->with('success', 'Email berhasil diupdate.');
    }
}
