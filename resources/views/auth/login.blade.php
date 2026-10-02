@extends('layouts.auth')
@section('title', 'Login')
@section('content')
    <h2>Login to Continue</h2>

    @if (session('status'))
        <div class="alert ok">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert err">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="field">
            <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder=" " required autofocus autocomplete="username">
            <label for="email">Email</label>
        </div>
        <div class="field">
            <input type="password" name="password" id="password" placeholder=" " required autocomplete="current-password">
            <label for="password">Password</label>
            <button type="button" class="toggle-pw" id="togglePw">Tampilkan</button>
        </div>
        <label class="remember">
            <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}> Ingat saya
        </label>
        <button type="submit" class="primary">Login</button>
    </form>

    <div class="links"><a href="{{ route('password.request') }}">Lupa password?</a></div>
@endsection
@push('scripts')
<script>
    document.getElementById('togglePw').addEventListener('click', function () {
        var p = document.getElementById('password');
        var show = p.type === 'password';
        p.type = show ? 'text' : 'password';
        this.textContent = show ? 'Sembunyikan' : 'Tampilkan';
    });
</script>
@endpush
