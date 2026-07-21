<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'DockPanel')</title>
    <style>
        :root {
            /* Warna dasar */
            --bg: #0a0e18;
            --bg-elevated: #10151f;
            --surface: #161d2b;
            --surface-hover: #1c2436;
            --border: #232d40;
            --border-light: #2c3850;

            /* Teks */
            --text: #e7ecf5;
            --text-muted: #8b96ab;
            --text-faint: #5b6579;

            /* Brand / aksen */
            --accent: #fb8b24;
            --accent-hover: #ff9d3d;
            --accent-dark: #1c1408;
            --accent-soft: #2a1a08;

            /* Status */
            --green: #34d399;
            --green-bg: #0c2e22;
            --amber: #fbbf24;
            --amber-bg: #2e2308;
            --red: #f87171;
            --red-bg: #2e1212;
            --gray: #94a3b8;
            --gray-bg: #1e2636;

            /* Lainnya */
            --radius-sm: 6px;
            --radius: 10px;
            --radius-lg: 14px;
            --shadow: 0 1px 3px rgba(0,0,0,0.35), 0 1px 2px rgba(0,0,0,0.24);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.35);
            --sidebar-w: 232px;
        }

        * { box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            margin: 0;
            -webkit-font-smoothing: antialiased;
            font-feature-settings: "tnum" 1;
        }

        h1, h2, h3 { letter-spacing: -0.01em; }
        h2 { font-size: 1.3rem; }
        h3 { font-size: 1.02rem; }

        a { transition: color 0.15s ease; }

        .app { display: flex; min-height: 100vh; }

        /* ── Sidebar ─────────────────────────────────────── */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--bg-elevated);
            border-right: 1px solid var(--border);
            flex-shrink: 0;
            position: fixed;
            top: 0; bottom: 0; left: 0;
            overflow-y: auto;
            transform: translateX(0);
            transition: transform 0.2s ease;
            z-index: 40;
        }
        .sidebar-brand {
            display: flex; align-items: center; gap: 0.55rem;
            padding: 1.15rem 1.25rem;
            font-weight: 700; font-size: 1.02rem;
            border-bottom: 1px solid var(--border);
            color: var(--text);
        }
        .sidebar-brand svg { color: var(--accent); }
        .sidebar-section {
            padding: 1.15rem 1.25rem 0.5rem;
            font-size: 0.68rem; letter-spacing: 0.07em; text-transform: uppercase;
            color: var(--text-faint); font-weight: 700;
        }
        .sidebar-link {
            display: flex; align-items: center; gap: 0.65rem;
            padding: 0.5rem 1.25rem; margin: 0.1rem 0.6rem;
            color: var(--text-muted); text-decoration: none;
            font-size: 0.87rem; font-weight: 500;
            border-radius: var(--radius-sm);
            transition: background 0.15s ease, color 0.15s ease;
        }
        .sidebar-link:hover { background: var(--surface-hover); color: var(--text); }
        .sidebar-link.active { background: var(--accent-soft); color: var(--accent); }
        .sidebar-link svg { flex-shrink: 0; }

        /* ── Topbar ──────────────────────────────────────── */
        .topbar {
            display: flex; justify-content: space-between; align-items: center;
            padding: 0.85rem 1.5rem;
            background: rgba(16, 21, 31, 0.85);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 20;
        }
        .topbar-title { font-weight: 700; font-size: 0.98rem; }
        .menu-toggle { display: none; background: none; border: none; color: var(--text); cursor: pointer; padding: 0.3rem; border-radius: var(--radius-sm); }
        .menu-toggle:hover { background: var(--surface-hover); }
        .topbar-right { display: flex; align-items: center; gap: 0.75rem; }
        .admin-badge {
            background: var(--accent); color: var(--accent-dark);
            padding: 0.2rem 0.55rem; border-radius: 999px;
            font-size: 0.68rem; font-weight: 700; letter-spacing: 0.03em;
        }

        /* ── Content ─────────────────────────────────────── */
        .content-wrap { flex: 1; margin-left: var(--sidebar-w); display: flex; flex-direction: column; min-width: 0; }
        main { padding: 1.75rem 1.5rem; max-width: 1040px; width: 100%; margin: 0 auto; flex: 1; }

        @media (max-width: 860px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .content-wrap { margin-left: 0; }
            .menu-toggle { display: block; }
        }

        .sidebar-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 30; }
        .sidebar-backdrop.open { display: block; }

        /* ── Card ────────────────────────────────────────── */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
        }

        /* ── Breadcrumb ──────────────────────────────────── */
        .breadcrumb { font-size: 0.83rem; color: var(--text-faint); margin-bottom: 1.1rem; }
        .breadcrumb a { color: var(--text-muted); text-decoration: none; }
        .breadcrumb a:hover { color: var(--accent); }
        .breadcrumb .sep { margin: 0 0.45rem; color: var(--border-light); }

        /* ── Table ───────────────────────────────────────── */
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 0.65rem 0.5rem; border-bottom: 1px solid var(--border); font-size: 0.88rem; }
        th { color: var(--text-faint); font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.03em; }
        tbody tr { transition: background 0.12s ease; }
        tbody tr:hover { background: var(--surface-hover); }

        /* ── Buttons ─────────────────────────────────────── */
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 0.55rem 1.05rem; border-radius: var(--radius-sm);
            border: 1px solid transparent; cursor: pointer; text-decoration: none;
            font-size: 0.85rem; font-weight: 600;
            transition: background 0.15s ease, border-color 0.15s ease, transform 0.05s ease;
        }
        .btn:active { transform: translateY(1px); }
        .btn-primary { background: var(--accent); color: var(--accent-dark); }
        .btn-primary:hover { background: var(--accent-hover); }
        .btn-secondary { background: var(--surface-hover); color: var(--text); border-color: var(--border-light); }
        .btn-secondary:hover { background: var(--border-light); }
        .btn-danger { background: var(--red-bg); color: var(--red); border-color: rgba(248,113,113,0.25); }
        .btn-danger:hover { background: rgba(248,113,113,0.18); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }

        /* ── Forms ───────────────────────────────────────── */
        label { display: block; font-size: 0.83rem; margin-bottom: 0.35rem; color: var(--text-muted); font-weight: 500; }
        input, select, textarea {
            width: 100%; padding: 0.6rem 0.7rem; margin-bottom: 1rem;
            border-radius: var(--radius-sm); border: 1px solid var(--border-light);
            background: var(--bg-elevated); color: var(--text); font-size: 0.9rem;
            font-family: inherit;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        input:focus, select:focus, textarea:focus {
            outline: none; border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(251, 139, 36, 0.15);
        }
        input::placeholder, textarea::placeholder { color: var(--text-faint); }
        .row { display: flex; gap: 1rem; flex-wrap: wrap; }
        .row > div { flex: 1; min-width: 140px; }

        /* ── Alerts ──────────────────────────────────────── */
        .error { color: var(--red); font-size: 0.85rem; margin-bottom: 1rem; }
        .success {
            background: var(--green-bg); color: var(--green);
            padding: 0.7rem 1rem; border-radius: var(--radius-sm);
            margin-bottom: 1.5rem; font-size: 0.88rem;
            border: 1px solid rgba(52, 211, 153, 0.2);
        }
        .muted { color: var(--text-muted); font-size: 0.85rem; }
        .actions form { display: inline; }
        code { background: var(--bg-elevated); border: 1px solid var(--border); padding: 0.15rem 0.4rem; border-radius: 4px; font-size: 0.85em; }

        /* ── Status badge ────────────────────────────────── */
        .status-badge {
            display: inline-flex; align-items: center; gap: 0.4rem;
            padding: 0.22rem 0.65rem; border-radius: 999px;
            font-size: 0.72rem; font-weight: 700; letter-spacing: 0.02em;
        }
        .status-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
        .status-running, .status-active { background: var(--green-bg); color: var(--green); }
        .status-installing, .status-starting, .status-stopping { background: var(--amber-bg); color: var(--amber); }
        .status-offline, .status-failed, .status-install_failed { background: var(--red-bg); color: var(--red); }
        .status-suspended, .status-maintenance { background: var(--gray-bg); color: var(--gray); }

        /* ── Empty state ─────────────────────────────────── */
        .empty-state { text-align: center; padding: 3rem 1rem; }
        .empty-state .icon { color: var(--text-faint); margin-bottom: 0.9rem; display: flex; justify-content: center; }
        .empty-state .icon svg { width: 42px; height: 42px; }
        .empty-state p { color: var(--text-muted); font-size: 0.9rem; margin: 0 0 1.1rem; }

        .btn svg, h1 svg, h2 svg { vertical-align: -4px; margin-right: 0.3rem; }

        /* ── Server card ─────────────────────────────────── */
        .server-card {
            display: flex; align-items: center; gap: 1rem;
            padding: 0.95rem 1.1rem; background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius); margin-bottom: 0.6rem;
            border-left: 3px solid var(--border-light);
            transition: border-color 0.15s ease;
        }
        .server-card:hover { border-color: var(--border-light); background: var(--surface-hover); }
        .server-card.status-running-border { border-left-color: var(--green); }
        .server-card.status-installing-border { border-left-color: var(--amber); }
        .server-card.status-offline-border, .server-card.status-install_failed-border { border-left-color: var(--red); }
        .server-card-icon {
            width: 36px; height: 36px; border-radius: var(--radius-sm);
            background: var(--surface-hover); display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; color: var(--text-muted);
        }
        .server-card-name { font-weight: 600; font-size: 0.92rem; }
        .server-card-name a { color: inherit; text-decoration: none; }
        .server-card-name a:hover { color: var(--accent); }
        .server-card-sub { font-size: 0.78rem; color: var(--text-faint); margin-top: 0.15rem; }
        .server-card-stats { display: flex; gap: 1.5rem; margin-left: auto; }
        .stat { min-width: 90px; }
        .stat-label { font-size: 0.7rem; color: var(--text-faint); display: flex; align-items: center; gap: 0.3rem; margin-bottom: 0.3rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.02em; }
        .stat-bar { height: 5px; border-radius: 3px; background: var(--bg-elevated); overflow: hidden; }
        .stat-bar-fill { height: 100%; background: var(--border-light); border-radius: 3px; width: 0%; transition: width 0.3s ease; }
        .stat-value { font-size: 0.7rem; color: var(--text-faint); margin-top: 0.25rem; }

        @media (max-width: 700px) {
            .server-card { flex-wrap: wrap; }
            .server-card-stats { width: 100%; margin-left: 0; margin-top: 0.6rem; justify-content: space-between; gap: 0.75rem; }
            .stat { min-width: 0; flex: 1; }
        }

        /* ── Footer ──────────────────────────────────────── */
        .app-footer {
            display: flex; justify-content: space-between; align-items: center;
            padding: 0.9rem 1.5rem; border-top: 1px solid var(--border);
            font-size: 0.75rem; color: var(--text-faint); margin-top: auto;
        }
        .app-footer a { color: var(--text-muted); text-decoration: none; }
        .app-footer a:hover { color: var(--accent); }
        .app-footer-right { display: flex; align-items: center; gap: 0.6rem; }
        .app-footer-version {
            background: var(--surface); border: 1px solid var(--border-light);
            color: var(--text-muted); padding: 0.15rem 0.55rem; border-radius: 999px; font-weight: 700;
        }
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

            <div class="sidebar-section">{{ auth()->user()->isRootAdmin() ? 'Basic Administration' : 'Navigation' }}</div>
            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->is('dashboard') ? 'active' : '' }}">
                @include('partials.icon', ['name' => 'home', 'size' => 17]) {{ auth()->user()->isRootAdmin() ? 'Overview' : 'My Servers' }}
            </a>

            @if (auth()->user()->isRootAdmin())
                <a href="{{ route('settings.edit') }}" class="sidebar-link {{ request()->is('settings') ? 'active' : '' }}">
                    @include('partials.icon', ['name' => 'settings', 'size' => 17]) Settings
                </a>
                <a href="{{ route('api-keys.index') }}" class="sidebar-link {{ request()->is('api-keys*') ? 'active' : '' }}">
                    @include('partials.icon', ['name' => 'api', 'size' => 17]) Application API
                </a>

                <div class="sidebar-section">Management</div>
                <a href="{{ route('databases.index') }}" class="sidebar-link {{ request()->is('databases*') ? 'active' : '' }}">
                    @include('partials.icon', ['name' => 'database', 'size' => 17]) Databases
                </a>
                <a href="{{ route('locations.index') }}" class="sidebar-link {{ request()->is('locations*') ? 'active' : '' }}">
                    @include('partials.icon', ['name' => 'location', 'size' => 17]) Locations
                </a>
                <a href="{{ route('nodes.index') }}" class="sidebar-link {{ request()->is('nodes*') ? 'active' : '' }}">
                    @include('partials.icon', ['name' => 'server', 'size' => 17]) Nodes
                </a>
                <a href="{{ route('servers.index') }}" class="sidebar-link {{ request()->is('servers*') ? 'active' : '' }}">
                    @include('partials.icon', ['name' => 'package', 'size' => 17]) Servers
                </a>
                <a href="{{ route('users.index') }}" class="sidebar-link {{ request()->is('users*') ? 'active' : '' }}">
                    @include('partials.icon', ['name' => 'users', 'size' => 17]) Users
                </a>

                <div class="sidebar-section">Service Management</div>
                <a href="{{ route('mounts.index') }}" class="sidebar-link {{ request()->is('mounts*') ? 'active' : '' }}">
                    @include('partials.icon', ['name' => 'mounts', 'size' => 17]) Mounts
                </a>
                <a href="{{ route('nests.index') }}" class="sidebar-link {{ request()->is('nests*') ? 'active' : '' }}">
                    @include('partials.icon', ['name' => 'globe', 'size' => 17]) Nests
                </a>
                <a href="{{ route('eggs.index') }}" class="sidebar-link {{ request()->is('eggs*') ? 'active' : '' }}">
                    @include('partials.icon', ['name' => 'egg', 'size' => 17]) Eggs
                </a>
            @endif

            <div class="sidebar-section">Account</div>
            <a href="{{ route('account.edit') }}" class="sidebar-link {{ request()->is('account') ? 'active' : '' }}">
                @include('partials.icon', ['name' => 'settings', 'size' => 17]) Account Settings
            </a>
            <a href="{{ route('account.api-credentials.index') }}" class="sidebar-link {{ request()->is('account/api-credentials*') ? 'active' : '' }}">
                @include('partials.icon', ['name' => 'api', 'size' => 17]) API Credentials
            </a>
            <a href="{{ route('account.two-factor.show') }}" class="sidebar-link {{ request()->is('account/two-factor*') ? 'active' : '' }}">
                @include('partials.icon', ['name' => 'settings', 'size' => 17]) Two-Factor
            </a>
            <a href="{{ route('account.activity') }}" class="sidebar-link {{ request()->is('account/activity*') ? 'active' : '' }}">
                @include('partials.icon', ['name' => 'api', 'size' => 17]) Activity
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
                    <span class="app-footer-version">v{{ config('app.version') }}</span>
                    <span>{{ defined('LARAVEL_START') ? number_format((microtime(true) - LARAVEL_START) * 1000) . 'ms' : '' }}</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
