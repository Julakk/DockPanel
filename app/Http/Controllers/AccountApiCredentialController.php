<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AccountApiCredentialController extends Controller
{
    public function index()
    {
        $tokens = auth()->user()->tokens()->orderBy('created_at', 'desc')->get();

        return view('account.api-credentials', compact('tokens'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $token = auth()->user()->createToken($validated['name']);

        return redirect()->route('account.api-credentials.index')->with(
            'success',
            "Token '{$validated['name']}' dibuat. Simpan sekarang, nggak bakal ditampilin lagi: {$token->plainTextToken}"
        );
    }

    public function destroy(int $tokenId)
    {
        $token = auth()->user()->tokens()->findOrFail($tokenId);
        $name = $token->name;
        $token->delete();

        return redirect()->route('account.api-credentials.index')->with('success', "Token '{$name}' dicabut.");
    }
}
