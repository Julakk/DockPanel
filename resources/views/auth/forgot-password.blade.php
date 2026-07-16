<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password - DockPanel</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #0f172a; color: #e2e8f0; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .card { background: #1e293b; padding: 2rem; border-radius: 12px; width: 320px; }
        h1 { font-size: 1.3rem; margin-bottom: 0.5rem; text-align: center; }
        p.desc { font-size: 0.85rem; color: #94a3b8; text-align: center; margin-bottom: 1.2rem; }
        label { display: block; font-size: 0.85rem; margin-bottom: 0.3rem; color: #94a3b8; }
        input { width: 100%; padding: 0.6rem; margin-bottom: 1rem; border-radius: 6px; border: 1px solid #334155; background: #0f172a; color: #e2e8f0; box-sizing: border-box; }
        button { width: 100%; padding: 0.7rem; background: #f97316; color: #0f172a; font-weight: 600; border: none; border-radius: 6px; cursor: pointer; }
        .error { color: #f87171; font-size: 0.85rem; margin-bottom: 1rem; }
        .success { color: #86efac; font-size: 0.85rem; margin-bottom: 1rem; }
        .back { display: block; text-align: center; margin-top: 1rem; color: #64748b; font-size: 0.85rem; text-decoration: none; }
    </style>
</head>
<body>
    <form class="card" method="POST" action="{{ route('password.email') }}">
        @csrf
        <h1>🐧 Lupa Password</h1>
        <p class="desc">Masukin email kamu, nanti dikirim link buat reset password.</p>

        @if (session('success'))
            <div class="success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <label for="email">Email</label>
        <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus>

        <button type="submit">Kirim Link Reset</button>

        <a href="{{ route('login') }}" class="back">← Kembali ke login</a>
    </form>
</body>
</html>
