@extends('layouts.auth')
@section('title', 'Verifikasi 2FA')
@section('content')
    <h2>Verifikasi 2FA</h2>
    <p class="desc">Masukin kode 6 digit dari authenticator app kamu, atau satu recovery code kalau authenticator nggak bisa diakses.</p>

    @if ($errors->any())
        <div class="alert err">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login.two-factor.verify') }}">
        @csrf
        <div class="field">
            <input type="text" name="code" id="code" placeholder=" " maxlength="32" autocomplete="one-time-code" autocapitalize="off" spellcheck="false" required autofocus>
            <label for="code">Kode 2FA atau recovery code</label>
        </div>
        <button type="submit" class="primary">Verifikasi</button>
    </form>

    <div class="links"><a href="{{ route('login') }}">Kembali ke login</a></div>
@endsection
