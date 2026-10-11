@extends(auth()->user()->isRootAdmin() ? 'layouts.app' : 'layouts.client')

@section('title', 'Two-Factor Authentication - DockPanel')

@section('breadcrumb')
    Account<span class="sep">&gt;</span>Two-Factor
@endsection

@section('content')
    <div style="display:flex; gap:1rem; margin-bottom:1.5rem; border-bottom:1px solid #263349; overflow-x:auto;">
        <a href="{{ route('account.edit') }}" style="padding:0.6rem 0; color:#94a3b8; text-decoration:none; font-size:0.9rem; white-space:nowrap;">Account</a>
        <a href="{{ route('account.api-credentials.index') }}" style="padding:0.6rem 0; color:#94a3b8; text-decoration:none; font-size:0.9rem; white-space:nowrap;">API Credentials</a>
        <a href="{{ route('account.two-factor.show') }}" style="padding:0.6rem 0; color:#f97316; text-decoration:none; border-bottom:2px solid #f97316; font-size:0.9rem; font-weight:600; white-space:nowrap;">Two-Factor</a>
        <a href="{{ route('account.activity') }}" style="padding:0.6rem 0; color:#94a3b8; text-decoration:none; font-size:0.9rem; white-space:nowrap;">Activity</a>
    </div>

    @if ($errors->any())
        <div class="error">
            @foreach ($errors->all() as $error)
                {{ $error }}<br>
            @endforeach
        </div>
    @endif

    @if ($user->hasTwoFactorEnabled())
        <div class="card">
            <h3 style="margin-top:0;">
                <span class="status-badge status-active">Aktif</span>
                Two-Factor Authentication
            </h3>
            <p class="muted">Akun kamu udah dilindungi 2FA. Kalau mau matiin, konfirmasi password dulu.</p>

            @if (session('recovery_codes'))
                <div style="background:#0f172a; border:1px solid #f97316; padding:0.8rem; border-radius:6px; margin:1rem 0;">
                    <p style="margin-top:0;"><strong>Simpan recovery code ini sekarang.</strong> Cuma ditampilin sekali. Tiap kode bisa dipakai satu kali buat login kalau authenticator app nggak bisa diakses.</p>
                    <div style="font-family:monospace; font-size:1rem; letter-spacing:0.05em; display:grid; grid-template-columns:repeat(auto-fit, minmax(9rem, 1fr)); gap:0.4rem;">
                        @foreach (session('recovery_codes') as $recoveryCode)
                            <span>{{ $recoveryCode }}</span>
                        @endforeach
                    </div>
                </div>
            @else
                @php($codesLeft = count($user->two_factor_recovery_codes ?? []))
                <p class="muted">Recovery code tersisa: {{ $codesLeft }}@if ($codesLeft === 0). Bikin sekarang biar nggak kekunci kalau HP hilang.@endif</p>
            @endif

            <form method="POST" action="{{ route('account.two-factor.recovery-codes') }}" style="margin-bottom:1rem;">
                @csrf
                <label for="regen_password">Password</label>
                <input type="password" name="password" id="regen_password" required>
                <button type="submit" class="btn btn-primary">Bikin ulang recovery code</button>
            </form>

            <form method="POST" action="{{ route('account.two-factor.disable') }}">
                @csrf
                @method('DELETE')
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
                <button type="submit" class="btn btn-danger">Matikan Two-Factor</button>
            </form>
        </div>
    @else
        <div class="card">
            <h3 style="margin-top:0;">Setup Two-Factor Authentication</h3>
            <p class="muted">Scan/masukin secret ini ke authenticator app (Google Authenticator, Authy, dll):</p>

            <p style="background:#0f172a; padding:0.8rem; border-radius:6px; font-family:monospace; font-size:1rem; letter-spacing:0.1em; word-break:break-all;">{{ $secret }}</p>

            <p class="muted" style="font-size:0.75rem; word-break:break-all;">URI manual: {{ $otpAuthUri }}</p>

            <form method="POST" action="{{ route('account.two-factor.enable') }}" style="margin-top:1rem;">
                @csrf
                <label for="code">Masukin kode 6 digit buat konfirmasi</label>
                <input type="text" name="code" id="code" inputmode="numeric" maxlength="6" required>
                <button type="submit" class="btn btn-primary">Aktifkan Two-Factor</button>
            </form>
        </div>
    @endif
@endsection
