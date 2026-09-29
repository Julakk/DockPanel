@extends('layouts.client')

@section('title', $server->name)

@section('content')
<style>
.dp-wrap{max-width:960px;margin:0 auto}
.dp-head{display:flex;flex-wrap:wrap;gap:.75rem;justify-content:space-between;align-items:center;margin-bottom:1rem}
.dp-card{background:var(--card,rgba(127,127,127,.08));border:1px solid var(--border,rgba(127,127,127,.25));border-radius:10px;padding:1rem;margin-bottom:1rem}
.dp-tabs{display:flex;gap:.25rem;flex-wrap:wrap;margin-bottom:1rem;overflow-x:auto}
.dp-tabs a{padding:.45rem .9rem;border-radius:8px;text-decoration:none;border:1px solid var(--border,rgba(127,127,127,.25));color:inherit;font-size:.9rem;white-space:nowrap}
.dp-tabs a.active{background:var(--primary,#3b82f6);color:#fff;border-color:transparent}
.dp-power{display:flex;gap:.4rem;flex-wrap:wrap}
.dp-power button,.dp-btn{padding:.45rem .9rem;border-radius:8px;border:1px solid var(--border,rgba(127,127,127,.35));background:transparent;color:inherit;cursor:pointer}
.dp-power button:disabled{opacity:.4;cursor:not-allowed}
.dp-power .p-start{border-color:var(--green)!important;color:var(--green)!important}
.dp-power .p-restart{border-color:var(--amber)!important;color:var(--amber)!important}
.dp-power .p-stop,.dp-power .p-kill{border-color:var(--red)!important;color:var(--red)!important}
.dp-res{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.75rem}
.dp-bar{height:8px;border-radius:99px;background:rgba(127,127,127,.25);overflow:hidden;margin:.35rem 0}
.dp-bar>span{display:block;height:100%;width:0;background:var(--primary,#3b82f6);transition:width .4s}
.dp-muted{opacity:.65;font-size:.85rem}
.dp-alert{padding:.6rem .9rem;border-radius:8px;margin-bottom:1rem}
.dp-ok{background:rgba(34,197,94,.15)}.dp-err{background:rgba(239,68,68,.15)}
.dp-console{background:#0b0d0f;color:#cbd5e1;font-family:monospace;font-size:.85rem;padding:1rem;border-radius:8px;min-height:180px;white-space:pre-wrap}
.dp-input{width:100%;padding:.5rem .7rem;border-radius:8px;border:1px solid var(--border,rgba(127,127,127,.35));background:transparent;color:inherit;box-sizing:border-box;margin-bottom:0}
.dp-table td{padding:.3rem .6rem .3rem 0;vertical-align:top}
.dp-field{margin-bottom:.9rem}
.dp-perms{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.3rem;margin:.5rem 0}
.dp-perms label{display:flex;align-items:center;gap:.4rem;margin:0}
.dp-perms input{width:auto;margin:0}
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
            @foreach ([['start','Start',$canStart],['restart','Restart',$canRestart],['stop','Stop',$canStop],['kill','Kill',$canStop]] as [$act,$label,$allowed])
                <button type="submit" name="action" value="{{ $act }}" class="p-{{ $act }}" @disabled($server->suspended || ! $allowed)>{{ $label }}</button>
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
        @foreach ($tabs as $key => $label)
            <a href="{{ route('client.servers.show', ['server' => $server, 'tab' => $key]) }}" class="{{ $tab === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if ($tab === 'console')
        <div class="dp-card">
            <div class="dp-console" id="dp-console-log">Menyambungkan ke Wings...</div>
            <form id="dp-console-form" style="margin-top:.75rem;display:flex;gap:.5rem">
                <input class="dp-input" type="text" id="dp-console-cmd" maxlength="255" placeholder="Ketik command, mis. say halo" @disabled($server->suspended)>
                <button class="dp-btn" type="submit" @disabled($server->suspended)>Kirim</button>
            </form>
            <div class="dp-muted" id="dp-console-status" style="margin-top:.4rem"></div>
        </div>
        <script>
        (function () {
            const tokenUrl = @json(route('client.servers.console-token', $server));
            const logEl = document.getElementById('dp-console-log');
            const statusEl = document.getElementById('dp-console-status');
            const form = document.getElementById('dp-console-form');
            const input = document.getElementById('dp-console-cmd');
            let ws = null;

            function append(line) {
                logEl.textContent += (logEl.textContent === 'Menyambungkan ke Wings...' ? '' : '\n') + line;
                logEl.scrollTop = logEl.scrollHeight;
            }

            async function connect() {
                try {
                    const r = await fetch(tokenUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                    const d = await r.json();
                    if (!r.ok || !d.token) {
                        statusEl.textContent = 'Gagal ambil token console: ' + (d.error || r.status);
                        return;
                    }
                    const proto = location.protocol === 'https:' ? 'wss' : 'ws';
                    const url = `${proto}://${d.ws_host}:${d.ws_port}/api/servers/{{ $server->uuid }}/ws/console?token=${encodeURIComponent(d.token)}`;
                    ws = new WebSocket(url);
                    ws.onopen = () => { statusEl.textContent = 'Console tersambung.'; logEl.textContent = ''; };
                    ws.onmessage = (ev) => append(ev.data);
                    ws.onerror = () => { statusEl.textContent = 'Koneksi console error.'; };
                    ws.onclose = () => {
                        statusEl.textContent = 'Console terputus, reconnect dalam 5 detik...';
                        setTimeout(connect, 5000);
                    };
                } catch (e) {
                    statusEl.textContent = 'Gagal konek: ' + e.message;
                    setTimeout(connect, 5000);
                }
            }

            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const cmd = input.value.trim();
                if (!cmd) return;
                if (ws && ws.readyState === WebSocket.OPEN) {
                    ws.send(cmd);
                    append('> ' + cmd);
                    input.value = '';
                } else {
                    statusEl.textContent = 'Belum tersambung ke console.';
                }
            });

            connect();
        })();
        </script>

    @elseif ($tab === 'files')
        <div class="dp-card">
            <strong>File Manager</strong>
            <p class="dp-muted">Belum tersedia. Menunggu proxy SFTP ke Wings (butuh VPS buat testing).</p>
            <p class="dp-muted" style="margin-bottom:0">Sementara, akses file lewat SFTP: host <code>{{ $server->node->fqdn ?? '-' }}</code>, port <code>{{ $server->node->daemon_sftp ?? '-' }}</code>.</p>
        </div>

    @elseif ($tab === 'databases')
        <div class="dp-card">
            <strong>Databases</strong>
            @forelse ($server->databases as $db)
                <div style="border-top:1px solid rgba(127,127,127,.25);margin-top:.6rem;padding-top:.6rem">
                    <table class="dp-table">
                        <tr><td class="dp-muted">Host</td><td><code>{{ $db->databaseHost->host ?? '-' }}:{{ $db->databaseHost->port ?? '' }}</code></td></tr>
                        <tr><td class="dp-muted">Database</td><td><code>{{ $db->database }}</code></td></tr>
                        <tr><td class="dp-muted">Username</td><td><code>{{ $db->username }}</code></td></tr>
                        <tr><td class="dp-muted">Password</td><td><details><summary style="cursor:pointer">Tampilkan</summary><code>{{ $db->password }}</code></details></td></tr>
                    </table>
                </div>
            @empty
                <p class="dp-muted" style="margin-bottom:0">Server ini belum punya database. Minta admin buat provision.</p>
            @endforelse
        </div>

    @elseif ($tab === 'schedules')
        @include('client.partials.schedules')

    @elseif ($tab === 'users')
        <div class="dp-card">
            <strong>Users dengan akses ke server ini</strong>
            <table style="margin-top:.5rem">
                <thead><tr><th>User</th><th>Peran</th><th></th></tr></thead>
                <tbody>
                    <tr><td>{{ $server->owner->name }}<div class="dp-muted">{{ $server->owner->email }}</div></td><td>Owner</td><td></td></tr>
                    @foreach ($server->subusers as $sub)
                        @php($perms = json_decode($sub->pivot->permissions ?? '[]', true) ?: [])
                        <tr>
                            <td>{{ $sub->name }}<div class="dp-muted">{{ $sub->email }}</div></td>
                            <td class="dp-muted">{{ count($perms) }} permission</td>
                            <td>
                                <form method="POST" action="{{ route('client.servers.users.destroy', [$server, $sub]) }}" data-confirm="Cabut akses {{ $sub->name }}?">
                                    @csrf @method('DELETE')
                                    <button class="dp-btn" type="submit">Cabut</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <form method="POST" action="{{ route('client.servers.users.store', $server) }}" class="dp-card">
            @csrf
            <strong>Tambah subuser</strong>
            <div class="dp-field" style="margin-top:.5rem">
                <div class="dp-muted">Email (harus sudah punya akun)</div>
                <input class="dp-input" type="email" name="email" required>
            </div>
            <div class="dp-muted">Permission</div>
            <div class="dp-perms">
                @foreach ($availablePermissions as $key => $label)
                    <label><input type="checkbox" name="permissions[]" value="{{ $key }}"> {{ $label }}</label>
                @endforeach
            </div>
            <button class="dp-btn" type="submit">Tambah</button>
        </form>

    @elseif ($tab === 'network')
        <div class="dp-card">
            <strong>Allocations</strong>
            <table style="margin-top:.5rem">
                <thead><tr><th>IP</th><th>Port</th><th></th></tr></thead>
                <tbody>
                    @forelse ($server->allocations as $a)
                        <tr>
                            <td>{{ $a->ip_alias ?: $a->ip }}</td>
                            <td>{{ $a->port }}</td>
                            <td>@if ($a->is_primary)<span class="status-badge status-active">Primary</span>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="dp-muted">Belum ada allocation.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    @elseif ($tab === 'startup')
        <div class="dp-card">
            <div class="dp-muted">Startup command</div>
            <pre class="dp-console" style="min-height:0">{{ $server->startup ?: '-' }}</pre>
            <div class="dp-muted">Docker image</div>
            <pre class="dp-console" style="min-height:0;margin-bottom:0">{{ $server->image ?: '-' }}</pre>
        </div>
        @php($visible = $server->serverVariables->filter(fn ($sv) => $sv->eggVariable && ($isAdmin || $sv->eggVariable->user_viewable)))
        @if ($visible->isNotEmpty())
            <form method="POST" action="{{ route('client.servers.startup.update', $server) }}" class="dp-card">
                @csrf @method('PUT')
                <strong>Variables</strong>
                @foreach ($visible as $sv)
                    @php($ev = $sv->eggVariable)
                    @php($editable = $isManager && ($isAdmin || $ev->user_editable))
                    <div class="dp-field" style="margin-top:.8rem">
                        <div>{{ $ev->name }} <code>{{ $ev->env_variable }}</code></div>
                        @if ($ev->description)<div class="dp-muted">{{ $ev->description }}</div>@endif
                        <input class="dp-input" type="text" name="variables[{{ $ev->id }}]" value="{{ $sv->variable_value }}" @disabled(! $editable)>
                    </div>
                @endforeach
                @if ($isManager)<button class="dp-btn" type="submit">Simpan variable</button>@endif
            </form>
        @endif

    @elseif ($tab === 'settings')
        <div class="dp-card">
            <table class="dp-table">
                <tr><td class="dp-muted">UUID</td><td>{{ $server->uuid }}</td></tr>
                <tr><td class="dp-muted">Node</td><td>{{ $server->node->name ?? '-' }}</td></tr>
                <tr><td class="dp-muted">Egg</td><td>{{ $server->egg->name ?? '-' }}</td></tr>
                <tr><td class="dp-muted">Memory</td><td>{{ $server->memory ? $server->memory.' MB' : 'Unlimited' }}</td></tr>
                <tr><td class="dp-muted">Disk</td><td>{{ $server->disk ? $server->disk.' MB' : 'Unlimited' }}</td></tr>
                <tr><td class="dp-muted">CPU</td><td>{{ $server->cpu ? $server->cpu.'%' : 'Unlimited' }}</td></tr>
                <tr><td class="dp-muted">SFTP</td><td><code>{{ $server->node->fqdn ?? '-' }}:{{ $server->node->daemon_sftp ?? '-' }}</code></td></tr>
            </table>
        </div>
        @if ($isManager)
            <form method="POST" action="{{ route('client.servers.rename', $server) }}" class="dp-card">
                @csrf @method('PUT')
                <strong>Detail server</strong>
                <div class="dp-field" style="margin-top:.6rem">
                    <div class="dp-muted">Nama</div>
                    <input class="dp-input" type="text" name="name" value="{{ old('name', $server->name) }}" maxlength="255" required>
                </div>
                <div class="dp-field">
                    <div class="dp-muted">Deskripsi</div>
                    <input class="dp-input" type="text" name="description" value="{{ old('description', $server->description) }}" maxlength="500">
                </div>
                <button class="dp-btn" type="submit">Simpan</button>
            </form>
        @endif

    @elseif ($tab === 'activity')
        <div class="dp-card">
            <strong>Aktivitas server</strong>
            <table style="margin-top:.5rem">
                <thead><tr><th>Event</th><th>User</th><th>Waktu</th></tr></thead>
                <tbody>
                    @forelse ($activities as $a)
                        <tr>
                            <td><code>{{ $a->event }}</code></td>
                            <td>{{ $a->user->name ?? '-' }}</td>
                            <td class="dp-muted">{{ $a->created_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="dp-muted">Belum ada aktivitas.</td></tr>
                    @endforelse
                </tbody>
            </table>
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
