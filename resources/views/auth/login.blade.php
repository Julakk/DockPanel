<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - DockPanel</title>
    <style>
        :root {
            --bg: #0a0e18; --surface: #161d2b; --border-light: #2c3850;
            --text: #e7ecf5; --text-muted: #8b96ab; --text-faint: #5b6579;
            --accent: #fb8b24; --accent-hover: #ff9d3d; --accent-dark: #1c1408;
            --red: #f87171; --red-bg: #2e1212;
        }
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--bg); color: var(--text);
            display: flex; align-items: center; justify-content: center;
            height: 100vh; margin: 0;
        }
        .card {
            background: var(--surface); border: 1px solid var(--border-light);
            padding: 2rem; border-radius: 14px; width: 320px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.35);
        }
        h1 { font-size: 1.35rem; margin-bottom: 1.3rem; text-align: center; letter-spacing: -0.01em; }
        label { display: block; font-size: 0.83rem; margin-bottom: 0.35rem; color: var(--text-muted); font-weight: 500; }
        input {
            width: 100%; padding: 0.6rem 0.7rem; margin-bottom: 1rem;
            border-radius: 6px; border: 1px solid var(--border-light);
            background: #10151f; color: var(--text); box-sizing: border-box; font-size: 0.9rem;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(251,139,36,0.15); }
        button {
            width: 100%; padding: 0.7rem; background: var(--accent); color: var(--accent-dark);
            font-weight: 700; border: none; border-radius: 6px; cursor: pointer; font-size: 0.9rem;
            transition: background 0.15s ease;
        }
        button:hover { background: var(--accent-hover); }
        .error { background: var(--red-bg); color: var(--red); font-size: 0.83rem; padding: 0.6rem 0.8rem; border-radius: 6px; margin-bottom: 1rem; }
        .forgot { display: block; text-align: center; margin-top: 1.1rem; color: var(--text-faint); font-size: 0.83rem; text-decoration: none; }
        .forgot:hover { color: var(--accent); }
    </style>
</head>
<body>
    <form class="card" method="POST" action="/login">
        @csrf
        <h1>🐧 DockPanel Login</h1>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <label for="email">Email</label>
        <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus>

        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>

        <button type="submit">Masuk</button>

        <a href="{{ route('password.request') }}" class="forgot">Lupa password?</a>
    </form>
</body>
</html>
