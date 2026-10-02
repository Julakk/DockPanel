<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - DockPanel</title>
    <style>
        :root {
            --bg: #12161b; --surface: #1f262e; --input-bg: #171c22; --border: #323d49;
            --text: #e7ecf5; --muted: #8b96ab; --faint: #5b6579;
            --accent: #22b8d6; --accent-hover: #4fd0ea; --accent-dark: #0e8fb0;
            --red: #f87171; --red-bg: #2e1212; --green: #34d399; --green-bg: #0c2e22;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--bg); color: var(--text);
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            min-height: 100vh; padding: 1.5rem 1rem;
        }
        .brand { font-size: 2.2rem; font-weight: 600; letter-spacing: -0.02em; margin-bottom: 1.4rem; text-align: center; }
        .brand span { color: var(--accent); }
        .card {
            width: 100%; max-width: 420px; background: var(--surface);
            border: 1px solid var(--border); border-radius: 6px;
            padding: 2rem 2rem 1.6rem; box-shadow: 0 8px 24px rgba(0,0,0,.4);
        }
        .card h2 { margin: 0 0 1.4rem; font-size: 1.5rem; font-weight: 500; text-align: center; }
        .card p.desc { margin: -.8rem 0 1.4rem; font-size: .85rem; color: var(--muted); text-align: center; }
        .field { position: relative; margin-bottom: 1.1rem; }
        .field input[type=email], .field input[type=password], .field input[type=text] {
            width: 100%; padding: 1.35rem .8rem .5rem; font-size: .95rem; color: var(--text);
            background: var(--input-bg); border: 1px solid var(--border); border-radius: 4px;
            transition: border-color .15s, box-shadow .15s;
        }
        .field label {
            position: absolute; left: .8rem; top: .95rem; font-size: .95rem; color: var(--muted);
            pointer-events: none; transition: all .15s ease;
        }
        .field input:focus + label, .field input:not(:placeholder-shown) + label {
            top: .38rem; font-size: .68rem; text-transform: uppercase; letter-spacing: .05em; color: var(--accent);
        }
        .field input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(34,184,214,.2); }
        .toggle-pw {
            position: absolute; right: .6rem; top: .8rem; background: none; border: 0; width: auto;
            color: var(--faint); font-size: .75rem; cursor: pointer; padding: .2rem .3rem;
        }
        .toggle-pw:hover { color: var(--accent); }
        .remember { display: flex; align-items: center; gap: .5rem; font-size: .85rem; color: var(--muted); margin-bottom: 1.2rem; }
        .remember input { accent-color: var(--accent); width: 15px; height: 15px; margin: 0; }
        button.primary {
            width: 100%; padding: .8rem; border: 0; border-radius: 4px; cursor: pointer;
            background: var(--accent-dark); color: #fff; font-weight: 700;
            font-size: .85rem; letter-spacing: .08em; text-transform: uppercase; transition: background .15s;
        }
        button.primary:hover { background: var(--accent-hover); }
        .alert { font-size: .85rem; padding: .65rem .85rem; border-radius: 4px; margin-bottom: 1.1rem; }
        .alert.err { background: var(--red-bg); color: var(--red); }
        .alert.ok { background: var(--green-bg); color: var(--green); }
        .links { text-align: center; margin-top: 1.3rem; }
        .links a { color: var(--faint); font-size: .8rem; text-decoration: none; letter-spacing: .03em; }
        .links a:hover { color: var(--accent); }
        .footer { margin-top: 1.5rem; font-size: .72rem; color: var(--faint); text-align: center; }
    </style>
</head>
<body>
    <div class="brand">Dock<span>Panel</span></div>
    <div class="card">
        @yield('content')
    </div>
    <div class="footer">&copy; {{ date('Y') }} DockPanel &middot; Ahmad Store</div>
    @stack('scripts')
</body>
</html>
