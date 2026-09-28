<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') - DockPanel</title>
    <link rel="stylesheet" href="{{ asset('css/dp-theme.css') }}">
    <style>
        body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;margin:0;
            min-height:100vh;display:flex;align-items:center;justify-content:center;text-align:center;padding:1.5rem}
        .dp-error{max-width:440px}
        .dp-error-code{font-size:5rem;font-weight:800;line-height:1;color:var(--accent)}
        .dp-error h1{margin:.6rem 0 .4rem;font-size:1.4rem}
        .dp-error p{color:var(--text-muted);margin:0 0 1.5rem}
        .btn{display:inline-flex;padding:.55rem 1.1rem;border-radius:var(--radius-sm);text-decoration:none;
            font-size:.88rem;font-weight:600;margin:0 .2rem;cursor:pointer}
    </style>
</head>
<body>
    <div class="dp-error">
        <div class="dp-error-code">@yield('code')</div>
        <h1>@yield('heading')</h1>
        <p>@yield('message')</p>
        <a class="btn btn-primary" href="{{ url('/dashboard') }}">Ke Dashboard</a>
        <a class="btn btn-secondary" href="javascript:history.back()">Kembali</a>
    </div>
</body>
</html>
