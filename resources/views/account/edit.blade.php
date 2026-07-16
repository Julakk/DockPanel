@extends('layouts.app')

@section('title', 'Account Settings - DockPanel')

@section('breadcrumb')
    Account<span class="sep">&gt;</span>Settings
@endsection

@section('content')
    <div style="display:flex; gap:1rem; margin-bottom:1.5rem; border-bottom:1px solid #263349; overflow-x:auto;">
        <a href="{{ route('account.edit') }}" style="padding:0.6rem 0; color:#f97316; text-decoration:none; border-bottom:2px solid #f97316; font-size:0.9rem; font-weight:600; white-space:nowrap;">Account</a>
        <a href="{{ route('account.api-credentials.index') }}" style="padding:0.6rem 0; color:#94a3b8; text-decoration:none; font-size:0.9rem; white-space:nowrap;">API Credentials</a>
        <a href="{{ route('account.two-factor.show') }}" style="padding:0.6rem 0; color:#94a3b8; text-decoration:none; font-size:0.9rem; white-space:nowrap;">Two-Factor</a>
        <a href="{{ route('account.activity') }}" style="padding:0.6rem 0; color:#94a3b8; text-decoration:none; font-size:0.9rem; white-space:nowrap;">Activity</a>
    </div>

    @if ($errors->any())
        <div class="error">
            @foreach ($errors->all() as $error)
                {{ $error }}<br>
            @endforeach
        </div>
    @endif

    <div class="row">
        <div>
            <div class="card">
                <h3 style="margin-top:0;">Update Password</h3>
                <form method="POST" action="{{ route('account.password.update') }}">
                    @csrf
                    @method('PUT')

                    <label for="current_password">Current Password</label>
                    <input type="password" name="current_password" id="current_password" required>

                    <label for="password">New Password</label>
                    <input type="password" name="password" id="password" required>
                    <p class="muted" style="margin-top:-0.6rem;">Minimal 8 karakter.</p>

                    <label for="password_confirmation">Confirm New Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required>

                    <button type="submit" class="btn btn-primary">Update Password</button>
                </form>
            </div>
        </div>

        <div>
            <div class="card">
                <h3 style="margin-top:0;">Update Email Address</h3>
                <form method="POST" action="{{ route('account.email.update') }}">
                    @csrf
                    @method('PUT')

                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required>

                    <label for="email_password">Confirm Password</label>
                    <input type="password" name="password" id="email_password" required>

                    <button type="submit" class="btn btn-primary">Update Email</button>
                </form>
            </div>

            <div class="card">
                <h3 style="margin-top:0;">Two-Factor Authentication</h3>
                <p class="muted">
                    Status: {{ $user->hasTwoFactorEnabled() ? 'Aktif ✅' : 'Belum aktif' }}
                </p>
                <a href="{{ route('account.two-factor.show') }}" class="btn btn-secondary">Kelola Two-Factor</a>
            </div>
        </div>
    </div>
@endsection
