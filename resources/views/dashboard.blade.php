<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - DockPanel</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #0f172a; color: #e2e8f0; margin: 0; padding: 2rem; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        h1 { font-size: 1.4rem; }
        .badge { background: #f97316; color: #0f172a; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        form { display: inline; }
        button { background: #334155; color: #e2e8f0; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="topbar">
        <h1>🐧 DockPanel — Halo, {{ $user->name }}
            @if ($user->isRootAdmin())
                <span class="badge">ADMIN</span>
            @endif
        </h1>
        <form method="POST" action="/logout">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </div>

    <p>Dashboard masih kosong — lanjut ke CRUD Node/Server berikutnya.</p>
</body>
</html>
