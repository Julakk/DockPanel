@extends('layouts.client')

@section('title', $server->name)

@section('content')
<style>
.dp-wrap{max-width:960px;margin:0 auto}
.dp-head{display:flex;flex-wrap:wrap;gap:.75rem;justify-content:space-between;align-items:center;margin-bottom:1rem}
.dp-card{background:var(--card,rgba(127,127,127,.08));border:1px solid var(--border,rgba(127,127,127,.25));border-radius:10px;padding:1rem;margin-bottom:1rem}
.dp-tabs{display:flex;gap:.25rem;flex-wrap:wrap;margin-bottom:1rem}
.dp-tabs a{padding:.45rem .9rem;border-radius:8px;text-decoration:none;border:1px solid var(--border,rgba(127,127,127,.25));color:inherit;font-size:.9rem}
.dp-tabs a.active{background:var(--primary,#3b82f6);color:#fff;border-color:transparent}
.dp-power{display:flex;gap:.4rem;flex-wrap:wrap}
.dp-power button,.dp-btn{padding:.45rem .9rem;border-radius:8px;border:1px solid var(--border,rgba(127,127,127,.35));background:transparent;color:inherit;cursor:pointer}
.dp-power button:disabled{opacity:.4;cursor:not-allowed}
.dp-res{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.75rem}
.dp-bar{height:8px;border-radius:99px;background:rgba(127,127,127,.25);overflow:hidden;margin:.35rem 0}
.dp-bar>span{display:block;height:100%;width:0;background:var(--primary,#3b82f6);transition:width .4s}
.dp-muted{opacity:.65;font-size:.85rem}
.dp-alert{padding:.6rem .9rem;border-radius:8px;margin-bottom:1rem}
.dp-ok{background:rgba(34,197,94,.15)}.dp-err{background:rgba(239,68,68,.15)}
.dp-console{background:#0b0d0f;color:#cbd5e1;font-family:monospace;font-size:.85rem;padding:1rem;border-radius:8px;min-height:180px;white-space:pre-wrap}
.dp-input{width:100%;padding:.5rem .7rem;border-radius:8px;border:1px solid var(--border,rgba(127,127,127,.35));background:transparent;color:inherit;box-sizing:border-box}
.dp-table td{padding:.3rem .6rem .3rem 0;vertical-align:top}
</style>

<div class="dp-wrap">
    @if (session('success'))<div class="dp-alert dp-ok">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="dp-alert dp-err">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="dp-alert dp-err">{{ $errors->first() }}</div>@endif

    <div class="dp-head">
        <div>
            <h1 style="margin:0">{{ $server->name }}</h1>
            <div class="dp-muted">
                {{ $server->uuid_short }}
                @if ($server->primaryAllocation)
                    · {{ $server->primaryAllocation->ip }}:{{ $server->primaryAllocation->port }}
                @endif
                @if ($server->suspended) · <strong>SUSPENDED</strong> @endif
            </div>
        </div>
        <form method="POST" action="{{ route('client.servers.power', $server) }}" class="dp-power">
            @csrf
            @foreach (['start' => 'Start', 'restart' => 'Restart', 'stop' => 'Stop', 'kill' => 'Kill'] as $act => $label)
                <button type="submit" name="action" value="{{ $act }}" @disabled($server->suspended)>{{ $label }}</button>
            @endforeach
        </form>
    </div>

    <div class="dp-card">
        <div class="dp-res">
            <div><div>CPU <span class="dp-muted" id="dp-cpu-t">-</span></div><div class="dp-bar"><span id="dp-cpu"></span></div></div>
            <div><div>Memory <span class="dp-muted" id="dp-mem-t">-</span></div><div class="dp-bar"><span id="dp-mem"></span></div></div>
            <div><div>Disk <span class="dp-muted" id="dp-disk-t">-</span></div><div class="dp-bar"><span id="dp-disk"></span></div></div>
        </div>
        <div class="dp-muted" id="dp-state">Status: memuat...</div>
    </div>

    @if ($server->expires_at)
        <div class="dp-card">
            Masa aktif sampai <strong>{{ $server->expires_at->format('d M Y H:i') }}</strong>
            <span class="dp-muted">({{ $server->expires_at->diffForHumans() }})</span>
            @if ($server->suspended && $server->suspension_reason === 'expired')
                — <strong>disuspend karena expired</strong>
            @endif
        </div>
    @endif

    @if (auth()->user()->root_admin)
        <form method="POST" action="{{ route('servers.expiry.update', $server) }}" class="dp-card" style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:end">
            @csrf
            @method('PUT')
            <div>
                <div class="dp-muted">Set tanggal expired (admin)</div>
                <input class="dp-input" type="datetime-local" name="expires_at"
                       value="{{ $server->expires_at?->format('Y-m-d\TH:i') }}">
            </div>
            <div>
                <div class="dp-muted">atau perpanjang (hari)</div>
                <input class="dp-input" type="number" name="extend_days" min="1" max="3650" placeholder="30" style="width:110px">
            </div>
            <button class="dp-btn" type="submit">Simpan</button>
        </form>
    @endif

    <div class="dp-tabs">
        @foreach (['console' => 'Console', 'files' => 'Files', 'settings' => 'Settings', 'startup' => 'Startup'] as $key => $label)
            <a href="{{ route('client.servers.show', ['server' => $server, 'tab' => $key]) }}" class="{{ $tab === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if ($tab === 'console')
        <div class="dp-card">
            <div class="dp-console">Console real-time belum aktif.
Butuh koneksi WebSocket ke Wings (roadmap: WebSocket console).
Sementara ini kamu bisa kirim command lewat form di bawah.</div>
            <form method="POST" action="{{ route('client.servers.command', $server) }}" style="margin-top:.75rem;display:flex;gap:.5rem">
                @csrf
                <input class="dp-input" type="text" name="command" maxlength="255" placeholder="Ketik command, mis. say halo" required @disabled($server->suspended)>
                <button class="dp-btn" type="submit" @disabled($server->suspended)>Kirim</button>
            </form>
        </div>
    @elseif ($tab === 'files')
        <div class="dp-card">
            <strong>File Manager</strong>
            <p class="dp-muted">Belum tersedia. Menunggu proxy SFTP ke Wings (butuh VPS buat testing).</p>
        </div>
    @elseif ($tab === 'settings')
        <div class="dp-card">
            <table class="dp-table">
                <tr><td class="dp-muted">Nama</td><td>{{ $server->name }}</td></tr>
                <tr><td class="dp-muted">UUID</td><td>{{ $server->uuid }}</td></tr>
                <tr><td class="dp-muted">Node</td><td>{{ $server->node->name ?? '-' }}</td></tr>
                <tr><td class="dp-muted">Egg</td><td>{{ $server->egg->name ?? '-' }}</td></tr>
                <tr><td class="dp-muted">Memory</td><td>{{ $server->memory ? $server->memory.' MB' : 'Unlimited' }}</td></tr>
                <tr><td class="dp-muted">Disk</td><td>{{ $server->disk ? $server->disk.' MB' : 'Unlimited' }}</td></tr>
                <tr><td class="dp-muted">CPU</td><td>{{ $server->cpu ? $server->cpu.'%' : 'Unlimited' }}</td></tr>
            </table>
        </div>
    @elseif ($tab === 'startup')
        <div class="dp-card">
            <div class="dp-muted">Startup command</div>
            <pre class="dp-console" style="min-height:0">{{ $server->startup ?: '-' }}</pre>
            <div class="dp-muted">Docker image</div>
            <pre class="dp-console" style="min-height:0">{{ $server->image ?: '-' }}</pre>
        </div>
    @endif
</div>

<script>
(function () {
    const url = @json(route('client.servers.resources', $server));
    const mb = b => b >= 1073741824 ? (b / 1073741824).toFixed(2) + ' GB' : (b / 1048576).toFixed(0) + ' MB';
    const pct = (v, l) => l > 0 ? Math.min(100, (v / l) * 100) : 0;
    const set = (id, p, t) => {
        document.getElementById('dp-' + id).style.width = p + '%';
        document.getElementById('dp-' + id + '-t').textContent = t;
    };
    async function tick() {
        try {
            const r = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            if (!r.ok) return;
            const d = await r.json();
            set('cpu', Math.min(100, d.cpu_percent), d.cpu_percent + '%');
            set('mem', pct(d.memory_bytes, d.memory_limit_bytes),
                mb(d.memory_bytes) + ' / ' + (d.memory_limit_bytes ? mb(d.memory_limit_bytes) : '∞'));
            set('disk', pct(d.disk_bytes, d.disk_limit_bytes),
                mb(d.disk_bytes) + ' / ' + (d.disk_limit_bytes ? mb(d.disk_limit_bytes) : '∞'));
            document.getElementById('dp-state').textContent =
                'Status: ' + d.state + (d.source === 'mock' ? ' (Wings belum terhubung)' : '');
        } catch (e) {}
    }
    tick();
    setInterval(tick, 5000);
})();
</script>
@endsection
