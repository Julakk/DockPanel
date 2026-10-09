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

/* ===== v0.18.0 tampilan ala Pterodactyl (hapus blok ini buat balik ke gaya lama) ===== */
body{background:#2d3948}
.dp-wrap{--hud-card:#374556;--hud-row:#3f4e61;--hud-line:#4a5a6e;--hud-mute:#9fb0c4;--hud-blue:#2563eb;--hud-grey:#5b6b7e;--hud-teal:#2aa5c4}
.dp-wrap .dp-card{background:var(--hud-card);border-color:var(--hud-line);border-radius:8px}
.dp-wrap .dp-stats{display:grid;grid-template-columns:repeat(2,1fr);gap:.6rem;margin-bottom:1rem}
@media(min-width:720px){.dp-wrap .dp-stats{grid-template-columns:repeat(4,1fr)}}
.dp-wrap .dp-stat{background:var(--hud-card);border:1px solid var(--hud-line);border-top:3px solid var(--hud-teal);border-radius:8px;padding:.7rem .8rem}
.dp-wrap .dp-stat-label{font-size:.68rem;letter-spacing:.06em;text-transform:uppercase;color:var(--hud-mute)}
.dp-wrap .dp-stat-value{font-size:1.05rem;font-weight:600;word-break:break-all}
.dp-wrap .dp-tabs{flex-wrap:nowrap;gap:0;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none;border-bottom:1px solid var(--hud-line);margin-bottom:1rem}
.dp-wrap .dp-tabs::-webkit-scrollbar{display:none}
.dp-wrap .dp-tabs a{flex:0 0 auto;border:0;border-radius:0;border-bottom:2px solid transparent;padding:.7rem .9rem;color:var(--hud-mute);font-size:.78rem;letter-spacing:.05em;text-transform:uppercase}
.dp-wrap .dp-tabs a.active{background:transparent;color:inherit;border-bottom-color:var(--hud-teal)}
.dp-wrap .dp-console{min-height:46vh;max-height:60vh;overflow:auto;border-radius:6px 6px 0 0;line-height:1.35}
#dp-console-form{margin:0!important;padding:.4rem;background:#0b0d0f;border-radius:0 0 6px 6px;border-top:1px solid #1d232a}
#dp-console-form .dp-input{border:0;background:transparent;font-family:monospace}
#fm .dp-btn{border-radius:4px;padding:.5rem .9rem;min-height:36px;font-weight:600;font-size:.82rem;background:var(--hud-grey);border-color:transparent;color:#fff}
#fm-upload,#fm-newfile{background:var(--hud-blue)!important}
#fm table{width:100%;border-collapse:collapse}
#fm tbody tr{background:var(--hud-row);border-bottom:1px solid var(--hud-line)}
#fm tbody tr:hover{background:#465770}
#fm td{padding:.55rem .5rem;vertical-align:middle;font-size:.85rem}
.fm-ico{display:inline-block;width:1.5rem;text-align:center}
.fm-name{color:inherit;text-decoration:none;word-break:break-all}
.fm-meta{text-align:right;white-space:nowrap;color:var(--hud-mute);font-size:.76rem;line-height:1.35}
#fm .fm-dots{background:transparent;border:0;font-size:1.3rem;line-height:1;padding:.2rem .6rem;min-height:0}
.fm-menu{position:absolute;z-index:60;min-width:160px;background:#2a3544;border:1px solid var(--hud-line,#4a5a6e);border-radius:6px;box-shadow:0 8px 24px rgba(0,0,0,.45);padding:.25rem}
.fm-menu button{display:block;width:100%;text-align:left;padding:.6rem .8rem;border:0;background:transparent;color:inherit;cursor:pointer;font-size:.85rem;border-radius:4px}
.fm-menu button:hover{background:#3b4a5c}
.fm-menu .fm-danger{color:#ff8a80}
.fm-crumb{display:flex;align-items:center;gap:.4rem;flex-wrap:wrap;font-size:.85rem;margin:.3rem 0 .6rem}
.fm-crumb a{color:inherit;text-decoration:none;opacity:.75}
.fm-crumb a:last-of-type{opacity:1;font-weight:600}
#fm input[type=checkbox]{width:auto!important;min-width:0;margin:0 .35rem 0 0;display:inline-block;flex:0 0 auto}
#fm-all{margin-right:.5rem!important}
.fm-bulk{display:none;gap:.5rem;align-items:center;padding:.5rem .6rem;margin-bottom:.5rem;background:#2a3544;border:1px solid var(--hud-line,#4a5a6e);border-radius:6px}

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
            <div class="dp-stat-label">Address</div>
            <div class="dp-stat-value">{{ $server->primaryAllocation ? (($server->primaryAllocation->ip_alias ?: $server->primaryAllocation->ip).':'.$server->primaryAllocation->port) : '-' }}</div>
        </div>
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
                <button class="dp-btn" id="fm-pull" type="button">Dari URL</button>
                <button class="dp-btn" id="fm-newfile" type="button">New File</button>
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
                extract: @json(route('client.servers.files.extract', $server)),
                compress: @json(route('client.servers.files.compress', $server)),
                chmod: @json(route('client.servers.files.chmod', $server)),
                pull: @json(route('client.servers.files.pull', $server)),
                del: @json(route('client.servers.files.delete', $server)),
            };
            const CSRF = @json(csrf_token());
            const $ = (id) => document.getElementById(id);
            let cwd = '/';
            let editing = null;

            const join = (d, n) => (d === '/' ? '' : d.replace(/\/$/, '')) + '/' + n;
            const parent = (d) => d === '/' ? '/' : (d.replace(/\/$/, '').split('/').slice(0, -1).join('/') || '/');
            const q = (u, o) => u + '?' + new URLSearchParams(o).toString();
            const size = (n) => n < 1024 ? n + ' Bytes' : n < 1048576 ? (n / 1024).toFixed(2) + ' KiB' : n < 1073741824 ? (n / 1048576).toFixed(2) + ' MiB' : (n / 1073741824).toFixed(2) + ' GiB';

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
                    renderCrumb();
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
                b.style.padding = '.4rem .8rem';
                b.style.margin = '.15rem .2rem .15rem 0';
                b.style.minHeight = '34px';
                b.style.fontWeight = '600';
                if (label === 'Hapus') { b.style.background = '#c0392b'; b.style.borderColor = '#c0392b'; b.style.color = '#fff'; }
                if (label === 'Extract') { b.style.background = '#2f81c7'; b.style.borderColor = '#2f81c7'; b.style.color = '#fff'; }
                b.textContent = label;
                b.addEventListener('click', fn);
                return b;
            }

            const selected = new Set();
            let currentEntries = [];
            let menuEl = null;
            const ARCH = /\.(zip|tar|tar\.gz|tgz)$/i;

            const fmtDate = (s) => {
                if (!s) return '';
                const d = new Date(s);
                if (isNaN(d)) return '';
                return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' + d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
            };

            function closeMenu() {
                if (menuEl) { menuEl.remove(); menuEl = null; }
            }
            document.addEventListener('click', closeMenu);

            function openMenu(ev, items) {
                ev.stopPropagation();
                closeMenu();
                const m = document.createElement('div');
                m.className = 'fm-menu';
                items.forEach((it) => {
                    const b = document.createElement('button');
                    b.type = 'button';
                    b.textContent = it.label;
                    if (it.danger) b.className = 'fm-danger';
                    b.addEventListener('click', (e2) => { e2.stopPropagation(); closeMenu(); it.fn(); });
                    m.appendChild(b);
                });
                document.body.appendChild(m);
                const r = ev.currentTarget.getBoundingClientRect();
                const w = m.offsetWidth;
                m.style.top = (r.bottom + window.scrollY + 4) + 'px';
                m.style.left = Math.max(8, Math.min(r.right + window.scrollX - w, window.innerWidth - w - 8)) + 'px';
                menuEl = m;
            }

            function syncChecks() {
                $('fm-list').querySelectorAll('input.fm-chk').forEach((c) => { c.checked = selected.has(c.dataset.name); });
            }

            function updateBulk() {
                const n = selected.size;
                const bar = $('fm-bulk');
                if (bar) {
                    bar.style.display = n ? 'flex' : 'none';
                    $('fm-bulk-n').textContent = n + ' dipilih';
                }
                const all = $('fm-all');
                if (all) {
                    all.checked = n > 0 && n === currentEntries.length;
                    all.indeterminate = n > 0 && n < currentEntries.length;
                }
            }

            function renderCrumb() {
                const el = $('fm-path');
                el.innerHTML = '';
                el.className = 'fm-crumb';
                el.style.fontFamily = 'inherit';
                const all = document.createElement('input');
                all.type = 'checkbox';
                all.id = 'fm-all';
                all.title = 'Pilih semua';
                all.addEventListener('change', () => {
                    selected.clear();
                    if (all.checked) currentEntries.forEach((x) => selected.add(x.name));
                    syncChecks();
                    updateBulk();
                });
                el.appendChild(all);
                const sep = () => el.appendChild(document.createTextNode('/'));
                const link = (label, path) => {
                    const a = document.createElement('a');
                    a.href = '#';
                    a.textContent = label;
                    a.addEventListener('click', (ev) => { ev.preventDefault(); load(path); });
                    return a;
                };
                sep();
                el.appendChild(document.createTextNode('home'));
                sep();
                el.appendChild(link('container', '/'));
                let acc = '';
                cwd.split('/').filter(Boolean).forEach((p) => {
                    acc += '/' + p;
                    sep();
                    el.appendChild(link(p, acc));
                });
                sep();
            }

            async function doRename(e, full) {
                const n = (prompt('Nama baru:', e.name) || '').trim();
                if (!n || n === e.name) return;
                try { await post(U.rename, { from: full, to: join(cwd, n) }); load(cwd); } catch (x) { msg(x.message); }
            }

            async function doDelete(e, full) {
                if (!confirm('Hapus ' + e.name + '?')) return;
                try { await post(U.del, { paths: [full] }); load(cwd); } catch (x) { msg(x.message); }
            }

            async function doZip(e, full) {
                msg('Mengompres ' + e.name + '...');
                try {
                    const r = await post(U.compress, { paths: [full], dest: full + '.zip' });
                    await load(cwd);
                    msg('Terkompres ke ' + e.name + '.zip' + (r && r.files != null ? ' (' + r.files + ' file).' : '.'));
                } catch (x) { msg(x.message); }
            }

            async function doExtract(e, full) {
                if (!confirm('Ekstrak ' + e.name + ' ke folder ini? File dengan nama sama akan ditimpa.')) return;
                msg('Mengekstrak ' + e.name + '...');
                try {
                    const r = await post(U.extract, { path: full });
                    await load(cwd);
                    msg('Terekstrak' + (r && r.files != null ? ' (' + r.files + ' file).' : '.'));
                } catch (x) { msg(x.message); }
            }

            async function doChmod(e, full) {
                const m = (prompt('Izin file: 755 = executable, 644 = biasa', '755') || '').trim();
                if (m !== '755' && m !== '644') { if (m) msg('Pilih 755 atau 644.'); return; }
                try {
                    await post(U.chmod, { path: full, executable: m === '755' });
                    await load(cwd);
                    msg('Izin ' + e.name + ' jadi ' + m + '.');
                } catch (x) { msg(x.message); }
            }

            function buildItems(e, full) {
                const it = [{ label: 'Rename', fn: () => doRename(e, full) }];
                if (!ARCH.test(e.name)) it.push({ label: 'Zip', fn: () => doZip(e, full) });
                if (!e.is_dir && ARCH.test(e.name)) it.push({ label: 'Extract', fn: () => doExtract(e, full) });
                if (!e.is_dir) it.push({ label: 'Izin (755 / 644)', fn: () => doChmod(e, full) });
                if (!e.is_dir) it.push({ label: 'Unduh', fn: () => { location.href = q(U.download, { path: full }); } });
                it.push({ label: 'Hapus', danger: true, fn: () => doDelete(e, full) });
                return it;
            }

            function render(entries) {
                selected.clear();
                currentEntries = entries.slice();
                const tb = $('fm-list');
                tb.innerHTML = '';
                entries.sort((a, b) => (b.is_dir - a.is_dir) || a.name.localeCompare(b.name));
                if (!entries.length) {
                    tb.innerHTML = '<tr><td class="dp-muted" style="padding:1rem">Folder kosong.</td></tr>';
                    updateBulk();
                    return;
                }
                entries.forEach((e) => {
                    const full = join(cwd, e.name);
                    const tr = document.createElement('tr');

                    const tdC = document.createElement('td');
                    tdC.style.width = '1%';
                    const chk = document.createElement('input');
                    chk.type = 'checkbox';
                    chk.className = 'fm-chk';
                    chk.dataset.name = e.name;
                    chk.addEventListener('change', () => {
                        if (chk.checked) selected.add(e.name); else selected.delete(e.name);
                        updateBulk();
                    });
                    tdC.appendChild(chk);

                    const tdN = document.createElement('td');
                    const ico = document.createElement('span');
                    ico.className = 'fm-ico';
                    ico.textContent = e.is_dir ? '\uD83D\uDCC1' : '\uD83D\uDCC4';
                    const a = document.createElement('a');
                    a.href = '#';
                    a.className = 'fm-name';
                    a.textContent = e.name;
                    a.addEventListener('click', (ev) => {
                        ev.preventDefault();
                        if (e.is_dir) load(full); else openFile(full);
                    });
                    tdN.appendChild(ico);
                    tdN.appendChild(a);

                    const tdM = document.createElement('td');
                    tdM.className = 'fm-meta';
                    if (!e.is_dir) {
                        const sz = document.createElement('div');
                        sz.textContent = size(e.size);
                        tdM.appendChild(sz);
                    }
                    const dt = document.createElement('div');
                    dt.textContent = fmtDate(e.modified);
                    tdM.appendChild(dt);

                    const tdA = document.createElement('td');
                    tdA.style.width = '1%';
                    const dots = document.createElement('button');
                    dots.type = 'button';
                    dots.className = 'dp-btn fm-dots';
                    dots.textContent = '\u22EF';
                    dots.setAttribute('aria-label', 'Aksi untuk ' + e.name);
                    dots.addEventListener('click', (ev) => openMenu(ev, buildItems(e, full)));
                    tdA.appendChild(dots);

                    tr.appendChild(tdC);
                    tr.appendChild(tdN);
                    tr.appendChild(tdM);
                    tr.appendChild(tdA);
                    tb.appendChild(tr);
                });
                updateBulk();
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

            $('fm-pull').addEventListener('click', async () => {
                const u = (prompt('URL file (http/https):') || '').trim();
                if (!u) return;
                let name = '';
                try { name = decodeURIComponent(new URL(u).pathname.split('/').pop() || ''); } catch (e) { msg('URL nggak valid.'); return; }
                name = (prompt('Simpan sebagai:', name || 'download') || '').trim();
                if (!name || name.indexOf('/') !== -1) return;
                msg('Mendownload...');
                try {
                    const r = await post(U.pull, { url: u, path: join(cwd, name) });
                    await load(cwd);
                    msg('Terdownload: ' + name + (r && r.size != null ? ' (' + size(r.size) + ').' : '.'));
                } catch (x) { msg(x.message); }
            });

            $('fm-newfile').addEventListener('click', () => {
                const n = (prompt('Nama file baru:') || '').trim();
                if (!n || n.indexOf('/') !== -1) return;
                editing = join(cwd, n);
                $('fm-ed-name').textContent = editing;
                $('fm-ed-text').value = '';
                $('fm-editor').style.display = 'block';
                $('fm-editor').scrollIntoView({ behavior: 'smooth' });
                msg('File dibuat setelah lu klik Simpan.');
            });

            (function () {
                const tbl = $('fm-list').closest('table') || $('fm-list').parentNode;
                const bar = document.createElement('div');
                bar.id = 'fm-bulk';
                bar.className = 'fm-bulk';
                const n = document.createElement('strong');
                n.id = 'fm-bulk-n';
                const bz = document.createElement('button');
                bz.type = 'button';
                bz.className = 'dp-btn';
                bz.textContent = 'Zip';
                bz.addEventListener('click', async () => {
                    const names = Array.from(selected);
                    if (!names.length) return;
                    let name = (prompt('Nama arsip:', 'arsip.zip') || '').trim();
                    if (!name) return;
                    if (!/\.zip$/i.test(name)) name += '.zip';
                    if (name.indexOf('/') !== -1) { msg('Nama arsip nggak boleh pakai /.'); return; }
                    msg('Mengompres ' + names.length + ' item...');
                    try {
                        const r = await post(U.compress, { paths: names.map((x) => join(cwd, x)), dest: join(cwd, name) });
                        await load(cwd);
                        msg('Terkompres ke ' + name + (r && r.files != null ? ' (' + r.files + ' file).' : '.'));
                    } catch (x) { msg(x.message); }
                });
                const bd = document.createElement('button');
                bd.type = 'button';
                bd.className = 'dp-btn';
                bd.style.background = '#c0392b';
                bd.textContent = 'Hapus';
                bd.addEventListener('click', async () => {
                    const names = Array.from(selected);
                    if (!names.length || !confirm('Hapus ' + names.length + ' item? Aksi ini tidak bisa dibatalkan.')) return;
                    try {
                        await post(U.del, { paths: names.map((x) => join(cwd, x)) });
                        await load(cwd);
                    } catch (x) { msg(x.message); }
                });
                bar.appendChild(n);
                bar.appendChild(bz);
                bar.appendChild(bd);
                tbl.parentNode.insertBefore(bar, tbl);
            })();

            load('/');
        })();
        </script>

    @elseif ($tab === 'databases')
        <div class="dp-card" style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;justify-content:space-between">
            <div>
                <strong>{{ $server->databases->count() }} / {{ $server->database_limit === null ? '∞' : $server->database_limit }}</strong>
                <div class="dp-muted">Database terpakai</div>
            </div>
            @if ($isManager && ($server->database_limit === null || $server->databases->count() < (int) $server->database_limit))
                <form method="POST" action="{{ route('client.servers.databases.store', $server) }}" style="display:flex;gap:.4rem;flex:1;max-width:520px;min-width:240px;flex-wrap:wrap">
                    @csrf
                    <input class="dp-input" type="text" name="database_name" maxlength="48" pattern="[a-zA-Z0-9_]+" placeholder="Nama database" required style="flex:2;min-width:140px">
                    <input class="dp-input" type="text" name="remote" maxlength="60" placeholder="Connections from (%)" style="flex:1;min-width:120px">
                    <button class="dp-btn" type="submit">Buat Database</button>
                </form>
            @elseif ($server->database_limit !== null && (int) $server->database_limit === 0)
                <span class="dp-muted">Pembuatan database dimatikan untuk server ini.</span>
            @endif
        </div>
        @forelse ($server->databases as $db)
            <div class="dp-card" style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap">
                <div style="min-width:140px;flex:1"><strong>{{ $db->database }}</strong><div class="dp-muted">Database</div></div>
                <div style="min-width:160px;flex:1"><code>{{ $db->endpoint() }}</code><div class="dp-muted">Endpoint</div></div>
                <div style="min-width:140px;flex:1"><code>{{ $db->username }}</code><div class="dp-muted">Username</div></div>
                <div style="min-width:100px;flex:1"><code>{{ $db->remote ?: "%" }}</code><div class="dp-muted">Connections from</div></div>
                <button type="button" class="dp-btn" onclick="var e=document.getElementById('dbd-{{ $db->id }}');e.style.display=e.style.display==='none'?'block':'none'">Detail</button>
                @if ($isManager)
                    @if (config('app.phpmyadmin_url') && config('app.phpmyadmin_signon_secret'))
                        <form method="POST" action="{{ route('client.servers.databases.phpmyadmin', [$server, $db->id]) }}" target="_blank">
                            @csrf
                            <button class="dp-btn" type="submit">phpMyAdmin</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('client.servers.databases.password', [$server, $db->id]) }}" data-confirm="Ganti password database {{ $db->database }}? Config game server yang pakai password lama harus diupdate.">
                        @csrf
                        <button class="dp-btn" type="submit">Rotate Password</button>
                    </form>
                    <form method="POST" action="{{ route('client.servers.databases.destroy', [$server, $db->id]) }}" data-confirm="Hapus database {{ $db->database }}? Semua datanya hilang.">
                        @csrf @method('DELETE')
                        <button class="dp-btn" type="submit">Hapus</button>
                    </form>
                @endif
                <div id="dbd-{{ $db->id }}" style="display:none;width:100%">
                    <div class="dp-muted">Password</div>
                    <code>{{ $db->password }}</code>
                    <div class="dp-muted" style="margin-top:.5rem">JDBC</div>
                    <code style="word-break:break-all">{{ $db->jdbcUrl() }}</code>
                </div>
            </div>
        @empty
            <div class="dp-card"><p class="dp-muted" style="margin:0">Server ini belum punya database.</p></div>
        @endforelse
        @if ($server->databases->isNotEmpty())
            <div class="dp-muted" style="text-align:right">Game server di Docker konek ke Endpoint di atas dengan username dan password database.</div>
        @endif

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
        @if ($server->status === 'restoring_backup')
            <div class="dp-card"><strong>Restore backup sedang berjalan.</strong> <span class="dp-muted">Server dikunci sampai selesai; halaman ini nyegerin sendiri.</span></div>
        @endif
        <div class="dp-card" style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;justify-content:space-between">
            <div>
                <strong>{{ $backupUsed }} / {{ $backupLimit }}</strong>
                <div class="dp-muted">Backup terpakai</div>
            </div>
            @if ($backupLimit === 0)
                <span class="dp-muted">Backup tidak bisa dibuat untuk server ini karena limit backup diset 0.</span>
            @elseif ($isManager && ! $server->suspended)
                <form method="POST" action="{{ route('client.servers.backups.store', $server) }}" style="display:flex;gap:.4rem;flex:1;max-width:420px;min-width:220px">
                    @csrf
                    <input class="dp-input" type="text" name="name" maxlength="100" placeholder="Nama backup (opsional)">
                    <button class="dp-btn" type="submit" @disabled($backupUsed >= $backupLimit)>Buat Backup</button>
                </form>
            @endif
        </div>
        @forelse ($backups as $b)
            <div class="dp-card" style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap">
                <div style="min-width:180px;flex:2">
                    <strong>{{ $b->name }}</strong>
                    <div class="dp-muted">{{ $b->created_at?->diffForHumans() }}</div>
                    @if ($b->status === 'completed' && (int) $b->changed_files > 0)
                        <div class="dp-muted" title="Server lagi jalan waktu backup dibuat, jadi sebagian file berubah atau hilang selama dibaca.">&#9888; {{ $b->changed_files }} file berubah selama dibaca</div>
                    @endif
                    @if ($b->status === 'failed' && $b->error)
                        <div class="dp-muted">{{ $b->error }}</div>
                    @endif
                </div>
                <div style="min-width:90px"><span class="status-badge {{ $b->badgeClass() }}">{{ $b->status }}</span></div>
                <div style="min-width:80px;flex:1">
                    @if ($b->status === 'completed')
                        {{ $b->sizeForHumans() }}
                    @else
                        <span class="dp-muted">-</span>
                    @endif
                </div>
                @if ($b->checksum)
                    <div style="min-width:120px;flex:1"><code>{{ substr($b->checksum, 0, 12) }}</code><div class="dp-muted">SHA-256</div></div>
                @endif
                @if ($isManager)
                    @if ($b->status === 'completed')
                        <a class="dp-btn" href="{{ route('client.servers.backups.download', [$server, $b->id]) }}">Download</a>
                        <form method="POST" action="{{ route('client.servers.backups.restore', [$server, $b->id]) }}" data-confirm="Restore backup {{ $b->name }}? SEMUA file server saat ini akan diganti dengan isi backup ini. Server harus dalam keadaan mati.">
                            @csrf
                            <button class="dp-btn" type="submit" @disabled($server->status === 'restoring_backup')>Restore</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('client.servers.backups.destroy', [$server, $b->id]) }}" data-confirm="Hapus backup {{ $b->name }}?">
                        @csrf @method('DELETE')
                        <button class="dp-btn" type="submit">Hapus</button>
                    </form>
                @endif
            </div>
        @empty
            <div class="dp-card dp-muted">Belum ada backup.</div>
        @endforelse
        @if ($backups->contains('status', 'creating') || $server->status === 'restoring_backup')
            <script>setTimeout(function () { location.reload(); }, 4000);</script>
        @endif

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
