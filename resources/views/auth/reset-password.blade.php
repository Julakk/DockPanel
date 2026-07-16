<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password - DockPanel</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #0f172a; color: #e2e8f0; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .card { background: #1e293b; padding: 2rem; border-radius: 12px; width: 320px; }
        h1 { font-size: 1.3rem; margin-bottom: 1.2rem; text-align: center; }
        label { display: block; font-size: 0.85rem; margin-bottom: 0.3rem; color: #94a3b8; }
        input { width: 100%; padding: 0.6rem; margin-bottom: 1rem; border-radius: 6px; border: 1px solid #334155; background: #0f172a; color: #e2e8f0; box-sizing: border-box; }
        button { width: 100%; padding: 0.7rem; background: #f97316; color: #0f172a; font-weight: 600; border: none; border-radius: 6px; cursor: pointer; }
        .error { color: #f87171; font-size: 0.85rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <form class="card" method="POST" action="{{ route('password.update') }}">
        @csrf
        <h1>🐧 Reset Password</h1>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <input type="hidden" name="token" value="{{ $token }}">

        <label for="email">Email</label>
        <input type="email" name="email" id="email" value="{{ old('email', $email) }}" required autofocus>

        <label for="password">Password Baru</label>
        <input type="password" name="password" id="password" required>

        <label for="password_confirmation">Konfirmasi Password</label>
        <input type="password" name="password_confirmation" id="password_confirmation" required>

        <button type="submit">Reset Password</button>
    </form>
</body>
</html>
