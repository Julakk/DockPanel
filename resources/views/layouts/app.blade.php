<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'DockPanel')</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; background: #0f172a; color: #e2e8f0; margin: 0; }
        .topbar { display: flex; justify-content: space-between; align-items: center; padding: 1rem 2rem; background: #1e293b; }
        .topbar h1 { font-size: 1.2rem; margin: 0; }
        .topbar nav a { color: #94a3b8; text-decoration: none; margin-right: 1.2rem; font-size: 0.9rem; }
        .topbar nav a:hover, .topbar nav a.active { color: #f97316; }
        .badge { background: #f97316; color: #0f172a; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.7rem; font-weight: 600; margin-left: 0.5rem; }
        main { padding: 2rem; max-width: 900px; margin: 0 auto; }
        .card { background: #1e293b; padding: 1.5rem; border-radius: 10px; margin-bottom: 1.5rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 0.6rem; border-bottom: 1px solid #334155; font-size: 0.9rem; }
        th { color: #94a3b8; font-weight: 500; }
        .btn { display: inline-block; padding: 0.5rem 1rem; border-radius: 6px; border: none; cursor: pointer; text-decoration: none; font-size: 0.85rem; font-weight: 600; }
        .btn-primary { background: #f97316; color: #0f172a; }
        .btn-secondary { background: #334155; color: #e2e8f0; }
        .btn-danger { background: #7f1d1d; color: #fca5a5; }
        label { display: block; font-size: 0.85rem; margin-bottom: 0.3rem; color: #94a3b8; }
        input, select, textarea { width: 100%; padding: 0.6rem; margin-bottom: 1rem; border-radius: 6px; border: 1px solid #334155; background: #0f172a; color: #e2e8f0; font-size: 0.9rem; }
        .row { display: flex; gap: 1rem; }
        .row > div { flex: 1; }
        .error { color: #f87171; font-size: 0.85rem; margin-bottom: 1rem; }
        .success { background: #14532d; color: #86efac; padding: 0.7rem 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.9rem; }
        .muted { color: #64748b; font-size: 0.85rem; }
        .actions form { display: inline; }
        code { background: #0f172a; padding: 0.1rem 0.4rem; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="topbar">
        <h1>🐧 DockPanel
            @if (auth()->user()?->isRootAdmin())
                <span class="badge">ADMIN</span>
            @endif
        </h1>
        <nav>
            <a href="/dashboard">Dashboard</a>
            <a href="{{ route('nodes.index') }}" class="{{ request()->is('nodes*') ? 'active' : '' }}">Nodes</a>
            <a href="{{ route('nests.index') }}" class="{{ request()->is('nests*') ? 'active' : '' }}">Nests</a>
            <a href="{{ route('eggs.index') }}" class="{{ request()->is('eggs*') ? 'active' : '' }}">Eggs</a>
            <a href="#">Servers</a>
        </nav>
        <form method="POST" action="/logout">
            @csrf
            <button type="submit" class="btn btn-secondary">Logout</button>
        </form>
    </div>

    <main>
        @if (session('success'))
            <div class="success">{{ session('success') }}</div>
        @endif

        @yield('content')
    </main>
</body>
</html>
