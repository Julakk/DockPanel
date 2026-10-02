@extends('layouts.app')

@section('title', $node->name . ' - Configuration')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>
    <a href="{{ route('nodes.index') }}">Nodes</a><span class="sep">&gt;</span>
    <a href="{{ route('nodes.show', $node) }}">{{ $node->name }}</a><span class="sep">&gt;</span>Configuration
@endsection

@section('content')
    @include('nodes._tabs', ['active' => 'config'])
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
        <h2 style="margin:0;">Configuration — {{ $node->name }}</h2>
        <a href="{{ route('nodes.show', $node) }}" class="btn btn-secondary">&larr; Kembali</a>
    </div>

    <div class="card">
        <p class="muted" style="margin-top:0;">
            Simpan sebagai <code>/etc/dockwings/config.json</code> di VPS node, lalu restart
            <code>systemctl restart dockwings</code>. Isinya termasuk token rahasia — jangan dibagikan.
        </p>
        <pre id="dp-node-config" style="background:var(--bg);border:1px solid var(--border);padding:1rem;border-radius:var(--radius);overflow-x:auto;margin:0 0 1rem;">{{ $json }}</pre>
        <button type="button" class="btn btn-primary" onclick="navigator.clipboard.writeText(document.getElementById('dp-node-config').textContent).then(()=>this.textContent='Tersalin ✓')">Salin config</button>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Install cepat di node</h3>
        <pre style="background:var(--bg);border:1px solid var(--border);padding:1rem;border-radius:var(--radius);overflow-x:auto;margin:0;">bash &lt;(curl -s https://raw.githubusercontent.com/Julakk/DockPanel/main/install.sh)</pre>
        <p class="muted" style="margin-bottom:0;">Pilih opsi 2 (Node/Wings), lalu tempel token di atas saat diminta.</p>
    </div>
@endsection
