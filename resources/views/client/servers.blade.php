@extends('layouts.client')

@section('title', 'My Servers - DockPanel')

@section('content')
    <h2 style="margin-top:0;">Halo, {{ $user->name }} @include('partials.icon', ['name' => 'sparkle', 'size' => 22])</h2>
    <p class="muted" style="margin-top:-0.6rem;">Ini daftar server yang kamu punya akses.</p>

    @if ($servers->isEmpty())
        <div class="card">
            <div class="empty-state">
                <div class="icon">@include('partials.icon', ['name' => 'package', 'size' => 40])</div>
                <p>Kamu belum punya server. Hubungi admin buat dibuatin server baru.</p>
            </div>
        </div>
    @else
        <input type="search" id="dp-server-filter" placeholder="Cari server..." style="max-width:320px">

        @foreach ($servers as $server)
            <a href="{{ route('client.servers.show', $server) }}" class="server-card status-{{ $server->status }}-border" data-server-card data-name="{{ strtolower($server->name) }}" data-url="{{ route('client.servers.resources', $server) }}" style="text-decoration:none;color:inherit">
                <div class="server-card-icon">
                    @include('partials.icon', ['name' => 'package', 'size' => 18])
                </div>

                <div>
                    <div class="server-card-name">{{ $server->name }}</div>
                    <div class="server-card-sub">{{ $server->node->name }} / {{ $server->egg->name }}@if ($server->expires_at) · exp {{ $server->expires_at->format('d M Y') }}@endif</div>
                </div>

                <span class="status-badge status-{{ $server->suspended ? 'suspended' : $server->status }}" data-state style="margin-left:0.5rem;">{{ $server->suspended ? 'suspended' : $server->status }}</span>

                <div class="server-card-stats">
                    <div class="stat">
                        <div class="stat-label">CPU</div>
                        <div class="stat-bar"><div class="stat-bar-fill" data-bar="cpu" style="width:0%;"></div></div>
                        <div class="stat-value" data-val="cpu">—</div>
                    </div>
                    <div class="stat">
                        <div class="stat-label">Memory</div>
                        <div class="stat-bar"><div class="stat-bar-fill" data-bar="mem" style="width:0%;"></div></div>
                        <div class="stat-value" data-val="mem">—</div>
                    </div>
                    <div class="stat">
                        <div class="stat-label">Disk</div>
                        <div class="stat-bar"><div class="stat-bar-fill" data-bar="disk" style="width:0%;"></div></div>
                        <div class="stat-value" data-val="disk">—</div>
                    </div>
                </div>
            </a>
        @endforeach

        <script>
        (function () {
            const cards = [...document.querySelectorAll('[data-server-card]')];
            const mb = b => b >= 1073741824 ? (b / 1073741824).toFixed(1) + 'G' : (b / 1048576).toFixed(0) + 'M';
            const pct = (v, l) => l > 0 ? Math.min(100, (v / l) * 100) : 0;
            const put = (c, k, p, t) => {
                c.querySelector('[data-bar="' + k + '"]').style.width = p + '%';
                c.querySelector('[data-val="' + k + '"]').textContent = t;
            };
            async function refresh(c) {
                try {
                    const r = await fetch(c.dataset.url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                    if (!r.ok) return;
                    const d = await r.json();
                    if (d.source === 'mock') return;
                    put(c, 'cpu', Math.min(100, d.cpu_percent), d.cpu_percent + '%');
                    put(c, 'mem', pct(d.memory_bytes, d.memory_limit_bytes), mb(d.memory_bytes) + (d.memory_limit_bytes ? ' / ' + mb(d.memory_limit_bytes) : ''));
                    put(c, 'disk', pct(d.disk_bytes, d.disk_limit_bytes), mb(d.disk_bytes) + (d.disk_limit_bytes ? ' / ' + mb(d.disk_limit_bytes) : ''));
                    const s = c.querySelector('[data-state]');
                    if (!s.textContent.includes('suspended')) {
                        s.textContent = d.state;
                        s.className = 'status-badge status-' + (d.state === 'running' ? 'running' : d.state === 'offline' ? 'offline' : 'starting');
                    }
                } catch (e) {}
            }
            cards.forEach(refresh);
            setInterval(() => cards.forEach(refresh), 10000);

            document.getElementById('dp-server-filter').addEventListener('input', e => {
                const q = e.target.value.trim().toLowerCase();
                cards.forEach(c => { c.style.display = c.dataset.name.includes(q) ? '' : 'none'; });
            });
        })();
        </script>
    @endif
@endsection
