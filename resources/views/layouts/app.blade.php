<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'DockPanel')</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; background: #0f172a; color: #e2e8f0; margin: 0; }

        /* Topbar & Nav */
        .topbar { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.25rem; background: #1e293b; flex-wrap: wrap; gap: 0.75rem; }
        .topbar h1 { font-size: 1.15rem; margin: 0; white-space: nowrap; }
        .topbar nav { display: flex; gap: 0.2rem; overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none; order: 3; width: 100%; }
        .topbar nav::-webkit-scrollbar { display: none; }
        .topbar nav a { color: #94a3b8; text-decoration: none; font-size: 0.85rem; padding: 0.4rem 0.7rem; border-radius: 6px; white-space: nowrap; flex-shrink: 0; }
        .topbar nav a:hover { color: #e2e8f0; background: #273449; }
        .topbar nav a.active { color: #f97316; background: #27180b; }
        .topbar-right { display: flex; align-items: center; gap: 0.6rem; order: 2; margin-left: auto; }
        .admin-badge { background: #f97316; color: #0f172a; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.7rem; font-weight: 600; margin-left: 0.5rem; }

        @media (min-width: 720px) {
            .topbar nav { order: 2; width: auto; overflow: visible; }
            .topbar-right { order: 3; margin-left: 0; }
        }

        main { padding: 1.5rem 1.25rem; max-width: 900px; margin: 0 auto; }
        .card { background: #1e293b; padding: 1.5rem; border-radius: 10px; margin-bottom: 1.5rem; }

        /* Breadcrumb */
        .breadcrumb { font-size: 0.85rem; color: #64748b; margin-bottom: 1rem; }
        .breadcrumb a { color: #94a3b8; text-decoration: none; }
        .breadcrumb a:hover { color: #f97316; }
        .breadcrumb .sep { margin: 0 0.4rem; color: #475569; }

        /* Table */
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 0.6rem; border-bottom: 1px solid #334155; font-size: 0.9rem; }
        th { color: #94a3b8; font-weight: 500; }

        /* Buttons */
        .btn { display: inline-block; padding: 0.5rem 1rem; border-radius: 6px; border: none; cursor: pointer; text-decoration: none; font-size: 0.85rem; font-weight: 600; }
        .btn-primary { background: #f97316; color: #0f172a; }
        .btn-secondary { background: #334155; color: #e2e8f0; }
        .btn-danger { background: #7f1d1d; color: #fca5a5; }

        /* Forms */
        label { display: block; font-size: 0.85rem; margin-bottom: 0.3rem; color: #94a3b8; }
        input, select, textarea { width: 100%; padding: 0.6rem; margin-bottom: 1rem; border-radius: 6px; border: 1px solid #334155; background: #0f172a; color: #e2e8f0; font-size: 0.9rem; }
        .row { display: flex; gap: 1rem; flex-wrap: wrap; }
        .row > div { flex: 1; min-width: 140px; }

        /* Alerts */
        .error { color: #f87171; font-size: 0.85rem; margin-bottom: 1rem; }
        .success { background: #14532d; color: #86efac; padding: 0.7rem 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.9rem; }
        .muted { color: #64748b; font-size: 0.85rem; }
        .actions form { display: inline; }
        code { background: #0f172a; padding: 0.1rem 0.4rem; border-radius: 4px; }

        /* Status badge */
        .status-badge { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.2rem 0.6rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
        .status-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
        .status-running, .status-active { background: #14532d; color: #4ade80; }
        .status-installing, .status-starting, .status-stopping { background: #451a03; color: #fbbf24; }
        .status-offline, .status-failed, .status-install_failed { background: #450a0a; color: #f87171; }
        .status-suspended, .status-maintenance { background: #334155; color: #94a3b8; }

        /* Empty state */
        .empty-state { text-align: center; padding: 2.5rem 1rem; }
        .empty-state .icon { color: #475569; margin-bottom: 0.8rem; display: flex; justify-content: center; }
        .empty-state .icon svg { width: 40px; height: 40px; }
        .empty-state p { color: #64748b; font-size: 0.9rem; margin: 0 0 1rem; }

        /* Inline icon dalam button/link */
        .btn svg, h1 svg, h2 svg { vertical-align: -4px; margin-right: 0.3rem; }
    </style>
</head>
<body>
    <div class="topbar">
        <h1>@include('partials.icon', ['name' => 'logo', 'size' => 20]) DockPanel
            @if (auth()->user()?->isRootAdmin())
                <span class="admin-badge">ADMIN</span>
            @endif
        </h1>

        <nav>
            <a href="{{ route('dashboard') }}" class="{{ request()->is('dashboard') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ route('nodes.index') }}" class="{{ request()->is('nodes*') ? 'active' : '' }}">Nodes</a>
            <a href="{{ route('nests.index') }}" class="{{ request()->is('nests*') ? 'active' : '' }}">Nests</a>
            <a href="{{ route('eggs.index') }}" class="{{ request()->is('eggs*') ? 'active' : '' }}">Eggs</a>
            <a href="{{ route('servers.index') }}" class="{{ request()->is('servers*') ? 'active' : '' }}">Servers</a>
        </nav>

        <div class="topbar-right">
            <form method="POST" action="/logout">
                @csrf
                <button type="submit" class="btn btn-secondary">Logout</button>
            </form>
        </div>
    </div>

    <main>
        @if (session('success'))
            <div class="success">{{ session('success') }}</div>
        @endif

        @hasSection('breadcrumb')
            <div class="breadcrumb">@yield('breadcrumb')</div>
        @endif

        @yield('content')
    </main>
</body>
</html>
