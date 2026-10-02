@extends('layouts.auth')
@section('title', 'Verifikasi 2FA')
@section('content')
    <h2>Verifikasi 2FA</h2>
    <p class="desc">Masukin kode 6 digit dari authenticator app kamu.</p>

    @if ($errors->any())
        <div class="alert err">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login.two-factor.verify') }}">
        @csrf
        <div class="field">
            <input type="text" name="code" id="code" placeholder=" " inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code" required autofocus>
            <label for="code">Kode 2FA</label>
        </div>
        <button type="submit" class="primary">Verifikasi</button>
    </form>

    <div class="links"><a href="{{ route('login') }}">Kembali ke login</a></div>
@endsection
