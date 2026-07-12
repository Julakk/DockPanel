<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'DockPanel')</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; background: #0f172a; color: #e2e8f0; margin: 0; }

        .app { display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar { width: 230px; background: #1a2333; flex-shrink: 0; position: fixed; top: 0; bottom: 0; left: 0; overflow-y: auto; transform: translateX(0); transition: transform 0.2s ease; z-index: 40; }
        .sidebar-brand { display: flex; align-items: center; gap: 0.5rem; padding: 1.1rem 1.25rem; font-weight: 700; font-size: 1.05rem; border-bottom: 1px solid #263349; }
        .sidebar-section { padding: 1rem 1.25rem 0.4rem; font-size: 0.7rem; letter-spacing: 0.05em; text-transform: uppercase; color: #4b5a75; font-weight: 600; }
        .sidebar-link { display: flex; align-items: center; gap: 0.65rem; padding: 0.55rem 1.25rem; color: #93a3bf; text-decoration: none; font-size: 0.88rem; border-left: 3px solid transparent; }
        .sidebar-link:hover { background: #212c40; color: #e2e8f0; }
        .sidebar-link.active { background: #212c40; color: #f97316; border-left-color: #f97316; }
        .sidebar-link svg { flex-shrink: 0; }

        /* Topbar (di dalam content area) */
        .topbar { display: flex; justify-content: space-between; align-items: center; padding: 0.9rem 1.5rem; background: #17202f; border-bottom: 1px solid #263349; }
        .topbar-title { font-weight: 700; font-size: 1rem; }
        .menu-toggle { display: none; background: none; border: none; color: #e2e8f0; cursor: pointer; padding: 0.3rem; }
        .topbar-right { display: flex; align-items: center; gap: 0.75rem; }
        .admin-badge { background: #f97316; color: #0f172a; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.7rem; font-weight: 600; }

        /* Content */
        .content-wrap { flex: 1; margin-left: 230px; display: flex; flex-direction: column; min-width: 0; }
        main { padding: 1.5rem; max-width: 1000px; width: 100%; margin: 0 auto; flex: 1; }

        @media (max-width: 860px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .content-wrap { margin-left: 0; }
            .menu-toggle { display: block; }
        }

        .sidebar-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 30; }
        .sidebar-backdrop.open { display: block; }

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

        .btn svg, h1 svg, h2 svg { vertical-align: -4px; margin-right: 0.3rem; }

        /* Server card ala Pterodactyl - resource usage bar */
        .server-card { display: flex; align-items: center; gap: 1rem; padding: 0.9rem 1rem; background: #1e293b; border-radius: 8px; margin-bottom: 0.6rem; border-left: 3px solid #334155; }
        .server-card.status-running-border { border-left-color: #4ade80; }
        .server-card.status-installing-border { border-left-color: #fbbf24; }
        .server-card.status-offline-border, .server-card.status-install_failed-border { border-left-color: #f87171; }
        .server-card-icon { width: 34px; height: 34px; border-radius: 6px; background: #334155; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #94a3b8; }
        .server-card-name { font-weight: 600; font-size: 0.92rem; }
        .server-card-name a { color: inherit; text-decoration: none; }
        .server-card-name a:hover { color: #f97316; }
        .server-card-sub { font-size: 0.78rem; color: #64748b; margin-top: 0.1rem; }
        .server-card-stats { display: flex; gap: 1.5rem; margin-left: auto; }
        .stat { min-width: 90px; }
        .stat-label { font-size: 0.7rem; color: #64748b; display: flex; align-items: center; gap: 0.3rem; margin-bottom: 0.25rem; }
        .stat-bar { height: 5px; border-radius: 3px; background: #0f172a; overflow: hidden; }
        .stat-bar-fill { height: 100%; background: #334155; border-radius: 3px; width: 0%; }
        .stat-value { font-size: 0.7rem; color: #475569; margin-top: 0.2rem; }

        @media (max-width: 700px) {
            .server-card { flex-wrap: wrap; }
            .server-card-stats { width: 100%; margin-left: 0; margin-top: 0.6rem; justify-content: space-between; gap: 0.75rem; }
            .stat { min-width: 0; flex: 1; }
        }

        /* Footer ala Pterodactyl */
        .app-footer { display: flex; justify-content: space-between; align-items: center; padding: 0.9rem 1.5rem; border-top: 1px solid #263349; font-size: 0.75rem; color: #4b5a75; margin-top: auto; }
        .app-footer a { color: #64748b; text-decoration: none; }
        .app-footer a:hover { color: #f97316; }
        .app-footer-right { display: flex; align-items: center; gap: 0.6rem; }
        .app-footer-version { background: #1e293b; color: #94a3b8; padding: 0.15rem 0.5rem; border-radius: 4px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="app">
        <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="document.getElementById('sidebar').classList.remove('open'); this.classList.remove('open');"></div>

        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                @include('partials.icon', ['name' => 'logo', 'size' => 20])
                DockPanel
            </div>

            <div class="sidebar-section">Basic Administration</div>
            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->is('dashboard') ? 'active' : '' }}">
                @include('partials.icon', ['name' => 'home', 'size' => 17]) Overview
            </a>
            <a href="#" class="sidebar-link">
                @include('partials.icon', ['name' => 'settings', 'size' => 17]) Settings
            </a>
            <a href="#" class="sidebar-link">
                @include('partials.icon', ['name' => 'api', 'size' => 17]) Application API
            </a>

            <div class="sidebar-section">Management</div>
            <a href="#" class="sidebar-link">
                @include('partials.icon', ['name' => 'database', 'size' => 17]) Databases
            </a>
            <a href="#" class="sidebar-link">
                @include('partials.icon', ['name' => 'location', 'size' => 17]) Locations
            </a>
            <a href="{{ route('nodes.index') }}" class="sidebar-link {{ request()->is('nodes*') ? 'active' : '' }}">
                @include('partials.icon', ['name' => 'server', 'size' => 17]) Nodes
            </a>
            <a href="{{ route('servers.index') }}" class="sidebar-link {{ request()->is('servers*') ? 'active' : '' }}">
                @include('partials.icon', ['name' => 'package', 'size' => 17]) Servers
            </a>
            <a href="#" class="sidebar-link">
                @include('partials.icon', ['name' => 'users', 'size' => 17]) Users
            </a>

            <div class="sidebar-section">Service Management</div>
            <a href="#" class="sidebar-link">
                @include('partials.icon', ['name' => 'mounts', 'size' => 17]) Mounts
            </a>
            <a href="{{ route('nests.index') }}" class="sidebar-link {{ request()->is('nests*') ? 'active' : '' }}">
                @include('partials.icon', ['name' => 'globe', 'size' => 17]) Nests
            </a>
            <a href="{{ route('eggs.index') }}" class="sidebar-link {{ request()->is('eggs*') ? 'active' : '' }}">
                @include('partials.icon', ['name' => 'egg', 'size' => 17]) Eggs
            </a>
        </aside>

        <div class="content-wrap">
            <div class="topbar">
                <div style="display:flex; align-items:center; gap:0.75rem;">
                    <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open'); document.getElementById('sidebarBackdrop').classList.toggle('open');">
                        @include('partials.icon', ['name' => 'menu', 'size' => 20])
                    </button>
                    <span class="topbar-title">@yield('title', 'DockPanel')</span>
                </div>

                <div class="topbar-right">
                    @if (auth()->user()?->isRootAdmin())
                        <span class="admin-badge">ADMIN</span>
                    @endif
                    <span class="muted">{{ auth()->user()?->name }}</span>
                    <form method="POST" action="/logout">
                        @csrf
                        <button type="submit" class="btn btn-secondary">@include('partials.icon', ['name' => 'logout', 'size' => 14])</button>
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

            <div class="app-footer">
                <div>Copyright &copy; 2026 <a href="https://github.com/Julakk/DockPanel" target="_blank" rel="noopener">DockPanel</a>.</div>
                <div class="app-footer-right">
                    <span class="app-footer-version">v0.4.0</span>
                    <span>{{ defined('LARAVEL_START') ? number_format((microtime(true) - LARAVEL_START) * 1000) . 'ms' : '' }}</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
