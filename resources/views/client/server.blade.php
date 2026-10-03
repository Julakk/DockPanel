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
            <div class="dp-crumb"><a href="{{ route('client.index') }}">Servers</a> <span>/</span></div>
            <h1 class="dp-title">{{ $server->name }}</h1>
            @if ($server->description)<div class="dp-muted">{{ $server->description }}</div>@endif
            <div class="dp-sub">
                <span class="dp-pill" id="dp-state" data-state="unknown">memuat</span>
                <code>{{ $server->uuid_short }}</code>
                @if ($server->primaryAllocation)
                    <code>{{ $server->primaryAllocation->ip }}:{{ $server->primaryAllocation->port }}</code>
                @endif
                @if ($server->suspended) <span class="dp-pill" data-state="suspended">suspended</span> @endif
            </div>
        </div>
        <form method="POST" action="{{ route('client.servers.power', $server) }}" class="dp-power">
            @csrf
            @foreach ([['start','Start',$canStart],['restart','Restart',$canRestart],['stop','Stop',$canStop],['kill','Kill',$canStop]] as [$act,$label,$allowed])
                <button type="submit" name="action" value="{{ $act }}" class="p-{{ $act }}" @disabled($server->suspended || ! $allowed)>{{ $label }}</button>
            @endforeach
        </form>
    </div>

    <div class="dp-stats">
        <div class="dp-stat">
            <div class="dp-stat-label">CPU</div>
            <div class="dp-stat-value" id="dp-cpu-t">-</div>
            <div class="dp-bar"><span id="dp-cpu"></span></div>
        </div>
        <div class="dp-stat">
            <div class="dp-stat-label">Memory</div>
            <div class="dp-stat-value" id="dp-mem-t">-</div>
            <div class="dp-bar"><span id="dp-mem"></span></div>
        </div>
        <div class="dp-stat">
            <div class="dp-stat-label">Disk</div>
            <div class="dp-stat-value" id="dp-disk-t">-</div>
            <div class="dp-bar"><span id="dp-disk"></span></div>
        </div>
    </div>
    <div class="dp-muted" id="dp-mock-note" style="display:none;margin:-.4rem 0 1rem">Wings belum terhubung, angka di atas belum data asli.</div>

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
        <div class="dp-card" id="fm">
            <div style="display:flex;flex-wrap:wrap;gap:.4rem;align-items:center;margin-bottom:.6rem">
                <button class="dp-btn" id="fm-up" type="button">&larr; Naik</button>
                <button class="dp-btn" id="fm-refresh" type="button">Refresh</button>
                <button class="dp-btn" id="fm-mkdir" type="button">Folder baru</button>
                <button class="dp-btn" id="fm-upload" type="button">Upload</button>
                <input type="file" id="fm-file" multiple style="display:none">
            </div>
            <div class="dp-muted" id="fm-path" style="margin-bottom:.5rem;font-family:monospace">/</div>
            <div id="fm-msg" class="dp-alert dp-err" style="display:none"></div>
            <div style="overflow-x:auto">
                <table class="dp-table" style="width:100%">
                    <tbody id="fm-list"><tr><td class="dp-muted">Memuat...</td></tr></tbody>
                </table>
            </div>
        </div>

        <div class="dp-card" id="fm-editor" style="display:none">
            <strong id="fm-ed-name"></strong>
            <textarea id="fm-ed-text" class="dp-input" spellcheck="false" style="min-height:320px;font-family:monospace;font-size:.85rem;margin:.6rem 0"></textarea>
            <button class="dp-btn" id="fm-ed-save" type="button">Simpan</button>
            <button class="dp-btn" id="fm-ed-close" type="button">Tutup</button>
        </div>

        <script>
        (function () {
            const U = {
                list: @json(route('client.servers.files.list', $server)),
                contents: @json(route('client.servers.files.contents', $server)),
                download: @json(route('client.servers.files.download', $server)),
                save: @json(route('client.servers.files.save', $server)),
                upload: @json(route('client.servers.files.upload', $server)),
                mkdir: @json(route('client.servers.files.mkdir', $server)),
                rename: @json(route('client.servers.files.rename', $server)),
                del: @json(route('client.servers.files.delete', $server)),
            };
            const CSRF = @json(csrf_token());
            const $ = (id) => document.getElementById(id);
            let cwd = '/';
            let editing = null;

            const join = (d, n) => (d === '/' ? '' : d.replace(/\/$/, '')) + '/' + n;
            const parent = (d) => d === '/' ? '/' : (d.replace(/\/$/, '').split('/').slice(0, -1).join('/') || '/');
            const q = (u, o) => u + '?' + new URLSearchParams(o).toString();
            const size = (n) => n < 1024 ? n + ' B' : n < 1048576 ? (n / 1024).toFixed(1) + ' KB' : (n / 1048576).toFixed(1) + ' MB';

            function msg(t) {
                const el = $('fm-msg');
                el.textContent = t || '';
                el.style.display = t ? 'block' : 'none';
            }

            async function api(url, opt) {
                opt = opt || {};
                opt.headers = Object.assign({ 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, opt.headers || {});
                const r = await fetch(url, opt);
                let data = null;
                try { data = await r.json(); } catch (e) {}
                if (!r.ok) throw new Error((data && (data.error || data.message)) || ('Error ' + r.status));
                return data;
            }

            const post = (url, body) => api(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body),
            });

            async function load(path) {
                msg('');
                try {
                    const d = await api(q(U.list, { path: path }));
                    cwd = path;
                    $('fm-path').textContent = cwd;
                    render(d.entries || []);
                } catch (e) {
                    msg(e.message);
                    $('fm-list').innerHTML = '';
                }
            }

            function btn(label, fn) {
                const b = document.createElement('button');
                b.className = 'dp-btn';
                b.type = 'button';
                b.style.padding = '.2rem .5rem';
                b.textContent = label;
                b.addEventListener('click', fn);
                return b;
            }

            function render(entries) {
                const tb = $('fm-list');
                tb.innerHTML = '';
                entries.sort((a, b) => (b.is_dir - a.is_dir) || a.name.localeCompare(b.name));
                if (!entries.length) {
                    tb.innerHTML = '<tr><td class="dp-muted">Folder kosong.</td></tr>';
                    return;
                }
                entries.forEach((e) => {
                    const tr = document.createElement('tr');
                    const full = join(cwd, e.name);

                    const tdName = document.createElement('td');
                    const a = document.createElement('a');
                    a.href = '#';
                    a.style.color = 'inherit';
                    a.textContent = (e.is_dir ? '[dir] ' : '') + e.name;
                    a.addEventListener('click', (ev) => {
                        ev.preventDefault();
                        e.is_dir ? load(full) : openFile(full);
                    });
                    tdName.appendChild(a);

                    const tdSize = document.createElement('td');
                    tdSize.className = 'dp-muted';
                    tdSize.textContent = e.is_dir ? '' : size(e.size);

                    const tdAct = document.createElement('td');
                    tdAct.style.whiteSpace = 'nowrap';
                    if (!e.is_dir) {
                        tdAct.appendChild(btn('Unduh', () => { location.href = q(U.download, { path: full }); }));
                        tdAct.appendChild(document.createTextNode(' '));
                    }
                    tdAct.appendChild(btn('Rename', async () => {
                        const n = prompt('Nama baru:', e.name);
                        if (!n || n === e.name) return;
                        try { await post(U.rename, { from: full, to: join(cwd, n) }); load(cwd); } catch (x) { msg(x.message); }
                    }));
                    tdAct.appendChild(document.createTextNode(' '));
                    tdAct.appendChild(btn('Hapus', async () => {
                        if (!confirm('Hapus ' + e.name + '?')) return;
                        try { await post(U.del, { paths: [full] }); load(cwd); } catch (x) { msg(x.message); }
                    }));

                    tr.appendChild(tdName);
                    tr.appendChild(tdSize);
                    tr.appendChild(tdAct);
                    tb.appendChild(tr);
                });
            }

            async function openFile(path) {
                msg('');
                try {
                    const d = await api(q(U.contents, { path: path }));
                    editing = path;
                    $('fm-ed-name').textContent = path;
                    $('fm-ed-text').value = d.content;
                    $('fm-editor').style.display = 'block';
                    $('fm-editor').scrollIntoView({ behavior: 'smooth' });
                } catch (e) {
                    msg(e.message);
                }
            }

            $('fm-ed-save').addEventListener('click', async () => {
                if (!editing) return;
                try {
                    await api(q(U.save, { path: editing }), {
                        method: 'POST',
                        headers: { 'Content-Type': 'text/plain' },
                        body: $('fm-ed-text').value,
                    });
                    msg('');
                    $('fm-ed-save').textContent = 'Tersimpan';
                    setTimeout(() => { $('fm-ed-save').textContent = 'Simpan'; }, 1500);
                } catch (e) { msg(e.message); }
            });

            $('fm-ed-close').addEventListener('click', () => {
                editing = null;
                $('fm-editor').style.display = 'none';
            });

            $('fm-up').addEventListener('click', () => load(parent(cwd)));
            $('fm-refresh').addEventListener('click', () => load(cwd));

            $('fm-mkdir').addEventListener('click', async () => {
                const n = prompt('Nama folder:');
                if (!n) return;
                try { await post(U.mkdir, { path: join(cwd, n) }); load(cwd); } catch (e) { msg(e.message); }
            });

            $('fm-upload').addEventListener('click', () => $('fm-file').click());
            $('fm-file').addEventListener('change', async (ev) => {
                const fd = new FormData();
                fd.append('path', cwd);
                Array.from(ev.target.files).forEach((f) => fd.append('files[]', f));
                try {
                    await api(U.upload, { method: 'POST', body: fd });
                    load(cwd);
                } catch (e) { msg(e.message); }
                ev.target.value = '';
            });

            load('/');
        })();
        </script>

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

    @elseif ($tab === 'backups')
        <div class="dp-card">
            @if ((int) ($server->backup_limit ?? 0) === 0)
                <p class="dp-muted" style="margin:0;text-align:center">Backup tidak bisa dibuat untuk server ini karena limit backup diset 0.</p>
            @else
                <p class="dp-muted" style="margin:0;text-align:center">Limit backup: {{ (int) $server->backup_limit }}. Pembuatan backup menunggu Wings aktif.</p>
            @endif
        </div>

    @elseif ($tab === 'network')
        @forelse ($server->allocations as $a)
            <div class="dp-card" style="display:flex;gap:.75rem;align-items:flex-start;flex-wrap:wrap">
                <div style="min-width:170px;padding-top:.35rem">
                    <code>{{ $a->ip_alias ?: $a->ip }}</code> <code>{{ $a->port }}</code>
                </div>
                @if ($isManager)
                    <form method="POST" action="{{ route('client.servers.allocations.notes', [$server, $a->id]) }}" style="flex:1;min-width:200px;display:flex;gap:.4rem">
                        @csrf @method('PUT')
                        <input class="dp-input" type="text" name="notes" value="{{ $a->notes }}" maxlength="255" placeholder="Notes">
                        <button class="dp-btn" type="submit">Simpan</button>
                    </form>
                    @if ($a->is_primary)
                        <span class="status-badge status-active" style="margin-top:.4rem">Primary</span>
                    @else
                        <form method="POST" action="{{ route('client.servers.allocations.primary', [$server, $a->id]) }}">
                            @csrf @method('PUT')
                            <button class="dp-btn" type="submit">Make Primary</button>
                        </form>
                        <form method="POST" action="{{ route('client.servers.allocations.destroy', [$server, $a->id]) }}" data-confirm="Lepas {{ $a->ip }}:{{ $a->port }} dari server ini?">
                            @csrf @method('DELETE')
                            <button class="dp-btn" type="submit">Hapus</button>
                        </form>
                    @endif
                @else
                    <div class="dp-muted" style="flex:1;padding-top:.35rem">{{ $a->notes }}</div>
                    @if ($a->is_primary)<span class="status-badge status-active" style="margin-top:.4rem">Primary</span>@endif
                @endif
            </div>
        @empty
            <div class="dp-card dp-muted">Belum ada allocation.</div>
        @endforelse

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
                <tr><td class="dp-muted">SFTP Username</td><td><code>{{ auth()->user()->email }}.{{ $server->uuid_short }}</code></td></tr>
                <tr><td class="dp-muted">SFTP Password</td><td class="dp-muted">Sama dengan password akun Panel</td></tr>
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
                            <td><code>{{ $a->event }}{{ ! empty($a->metadata['action']) ? '.'.$a->metadata['action'] : '' }}</code>
                                <div class="dp-muted">{{ $a->metadata['email'] ?? $a->metadata['name'] ?? $a->metadata['allocation'] ?? '' }}</div></td>
                            <td>{{ $a->user->name ?? '-' }}<div class="dp-muted">{{ $a->ip ?? '' }}</div></td>
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
            const st = document.getElementById('dp-state');
            st.textContent = d.state;
            st.dataset.state = d.state;
            document.getElementById('dp-mock-note').style.display = d.source === 'mock' ? 'block' : 'none';
        } catch (e) {}
    }
    tick();
    setInterval(tick, 5000);
})();
</script>
@endsection
