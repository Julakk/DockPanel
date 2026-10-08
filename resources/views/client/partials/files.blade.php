{{-- File manager: dirender JS, semua operasi lewat ClientFileController (proxy ke Wings) --}}
@php($fmWrite = (bool) ($canFilesWrite ?? false) && ! $server->suspended)
<style>
.fm-bar{display:flex;flex-wrap:wrap;gap:.6rem;justify-content:space-between;align-items:center;margin-bottom:.8rem}
.fm-crumb{display:flex;flex-wrap:wrap;align-items:center;gap:.3rem;font-size:.9rem}
.fm-crumb a{color:var(--accent);text-decoration:none;cursor:pointer}
.fm-crumb a:hover{text-decoration:underline}
.fm-sep{color:var(--text-faint,#6b7785)}
.fm-tools{display:flex;flex-wrap:wrap;gap:.4rem}
.fm-tools .dp-btn:disabled{opacity:.4;cursor:not-allowed}
.fm-name a{color:inherit;text-decoration:none;cursor:pointer}
.fm-name a.is-dir{color:var(--accent);font-weight:600}
.fm-name a:hover{text-decoration:underline}
.fm-num{white-space:nowrap;color:var(--text-muted,#9aa7b5);font-size:.82rem}
.fm-act{white-space:nowrap;text-align:right}
.fm-act .dp-btn{padding:.25rem .55rem;font-size:.78rem;margin-left:.2rem;text-decoration:none;display:inline-block}
.fm-check{width:auto!important;margin:0!important}
#fm-editor{margin-top:1rem;border-top:1px solid var(--border,rgba(127,127,127,.25));padding-top:.8rem}
#fm-edit-ta{width:100%;min-height:340px;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:.85rem;line-height:1.45;white-space:pre;tab-size:4;resize:vertical;margin-bottom:.6rem}
#fm.is-busy{opacity:.6;pointer-events:none}
</style>

<div class="dp-card" id="fm">
    <div class="fm-bar">
        <div class="fm-crumb" id="fm-crumb"></div>
        <div class="fm-tools">
            <button type="button" class="dp-btn" id="fm-refresh">Refresh</button>
            @if ($fmWrite)
                <button type="button" class="dp-btn" id="fm-newfile">File baru</button>
                <button type="button" class="dp-btn" id="fm-newdir">Folder baru</button>
                <button type="button" class="dp-btn" id="fm-upload">Upload</button>
                <input type="file" id="fm-file" multiple hidden>
                <button type="button" class="dp-btn" id="fm-delsel" disabled>Hapus terpilih</button>
            @endif
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    @if ($fmWrite)<th style="width:28px"><input type="checkbox" class="fm-check" id="fm-all" aria-label="Pilih semua"></th>@endif
                    <th>Nama</th><th>Ukuran</th><th>Diubah</th><th></th>
                </tr>
            </thead>
            <tbody id="fm-body"></tbody>
        </table>
    </div>
    <div class="dp-muted" id="fm-empty" hidden style="padding:.8rem 0">Folder ini kosong.</div>

    <div id="fm-editor" hidden>
        <div style="display:flex;flex-wrap:wrap;gap:.5rem;justify-content:space-between;align-items:center;margin-bottom:.5rem">
            <code id="fm-edit-path"></code>
            <span class="dp-muted" id="fm-edit-hint"></span>
        </div>
        <textarea id="fm-edit-ta" spellcheck="false" autocomplete="off"></textarea>
        <div style="display:flex;gap:.5rem">
            @if ($fmWrite)<button type="button" class="dp-btn" id="fm-save">Simpan</button>@endif
            <button type="button" class="dp-btn" id="fm-close">Tutup</button>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var U = {
        list: @json(route('client.servers.files.list', $server)),
        contents: @json(route('client.servers.files.contents', $server)),
        download: @json(route('client.servers.files.download', $server)),
        save: @json(route('client.servers.files.save', $server)),
        upload: @json(route('client.servers.files.upload', $server)),
        mkdir: @json(route('client.servers.files.mkdir', $server)),
        rename: @json(route('client.servers.files.rename', $server)),
        extract: @json(route('client.servers.files.extract', $server)),
        del: @json(route('client.servers.files.delete', $server))
    };
    var CSRF = @json(csrf_token());
    var WRITE = @json($fmWrite);
    var LIMIT = 2 * 1024 * 1024;

    var cwd = '/', entries = [], selected = {}, editPath = null;

    function $(id) { return document.getElementById(id); }
    function enc(s) { return encodeURIComponent(s); }
    function norm(p) { p = ('/' + p).replace(/\/+/g, '/'); return p.length > 1 ? p.replace(/\/$/, '') : p; }
    function join(d, n) { return norm(d + '/' + n); }
    function parentOf(p) { var i = p.lastIndexOf('/'); return i <= 0 ? '/' : p.slice(0, i); }
    function validName(n) { return !!n && n !== '.' && n !== '..' && n.indexOf('/') === -1 && n.indexOf('\\') === -1; }
    function fmtSize(b) {
        if (b < 1024) return b + ' B';
        if (b < 1048576) return (b / 1024).toFixed(1) + ' KB';
        if (b < 1073741824) return (b / 1048576).toFixed(1) + ' MB';
        return (b / 1073741824).toFixed(2) + ' GB';
    }
    function toast(t, type) { if (window.dpToast) window.dpToast(t, type || 'success'); else alert(t); }
    function busy(on) { $('fm').classList.toggle('is-busy', on); }

    async function req(url, opt) {
        opt = opt || {};
        opt.credentials = 'same-origin';
        opt.headers = Object.assign({
            'Accept': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'X-Requested-With': 'XMLHttpRequest'
        }, opt.headers || {});
        var r;
        try { r = await fetch(url, opt); } catch (e) { throw new Error('Gagal menghubungi Panel.'); }
        var d = null;
        try { d = await r.json(); } catch (e) {}
        if (!r.ok) throw new Error((d && (d.error || d.message)) || ('Error ' + r.status));
        return d || {};
    }

    function mk(tag, cls, text) {
        var e = document.createElement(tag);
        if (cls) e.className = cls;
        if (text !== undefined) e.textContent = text;
        return e;
    }
    function btn(label, fn) {
        var b = mk('button', 'dp-btn', label);
        b.type = 'button';
        b.addEventListener('click', fn);
        return b;
    }

    function updateSel() {
        var n = Object.keys(selected).length;
        var d = $('fm-delsel');
        if (d) { d.disabled = n === 0; d.textContent = n ? 'Hapus terpilih (' + n + ')' : 'Hapus terpilih'; }
    }

    function crumb(path, label) {
        var a = mk('a', '', label);
        a.addEventListener('click', function () { load(path); });
        return a;
    }

    function render() {
        var c = $('fm-crumb');
        c.textContent = '';
        c.appendChild(crumb('/', 'root'));
        var acc = '';
        cwd.split('/').filter(Boolean).forEach(function (p) {
            acc += '/' + p;
            c.appendChild(mk('span', 'fm-sep', '/'));
            c.appendChild(crumb(acc, p));
        });

        var body = $('fm-body');
        body.textContent = '';
        selected = {};
        updateSel();
        if ($('fm-all')) $('fm-all').checked = false;

        function cell(cls) { var td = mk('td', cls); return td; }

        if (cwd !== '/') {
            var up = mk('tr');
            if (WRITE) up.appendChild(cell());
            var tdUp = cell('fm-name');
            var aUp = mk('a', 'is-dir', '..');
            aUp.addEventListener('click', function () { load(parentOf(cwd)); });
            tdUp.appendChild(aUp);
            up.appendChild(tdUp);
            up.appendChild(cell()); up.appendChild(cell()); up.appendChild(cell());
            body.appendChild(up);
        }

        entries.forEach(function (e) {
            var tr = mk('tr');

            if (WRITE) {
                var tdc = cell();
                var cb = mk('input', 'fm-check');
                cb.type = 'checkbox';
                cb.setAttribute('aria-label', 'Pilih ' + e.name);
                cb.addEventListener('change', function () {
                    if (cb.checked) selected[e.name] = true; else delete selected[e.name];
                    updateSel();
                });
                tdc.appendChild(cb);
                tr.appendChild(tdc);
            }

            var tdn = cell('fm-name');
            var a = mk('a', e.is_dir ? 'is-dir' : '', e.is_dir ? e.name + '/' : e.name);
            a.addEventListener('click', function () {
                if (e.is_dir) load(join(cwd, e.name)); else edit(e);
            });
            tdn.appendChild(a);
            tr.appendChild(tdn);

            tr.appendChild(Object.assign(cell('fm-num'), { textContent: e.is_dir ? '-' : fmtSize(e.size) }));
            var when = e.modified ? new Date(e.modified).toLocaleString('id-ID') : '-';
            tr.appendChild(Object.assign(cell('fm-num'), { textContent: when }));

            var tda = cell('fm-act');
            if (!e.is_dir) {
                var dl = mk('a', 'dp-btn', 'Download');
                dl.href = U.download + '?path=' + enc(join(cwd, e.name));
                tda.appendChild(dl);
            }
            if (WRITE) {
                if (!e.is_dir && /\.(zip|tar|tar\.gz|tgz)$/i.test(e.name)) {
                    tda.appendChild(btn('Extract', function () { extractArchive(e); }));
                }
                tda.appendChild(btn('Rename', function () { rename(e); }));
                tda.appendChild(btn('Hapus', function () { delNames([e.name]); }));
            }
            tr.appendChild(tda);
            body.appendChild(tr);
        });

        $('fm-empty').hidden = entries.length > 0 || cwd !== '/' ? true : false;
        if (entries.length === 0 && cwd !== '/') $('fm-empty').hidden = false;
    }

    async function load(path) {
        busy(true);
        try {
            var d = await req(U.list + '?path=' + enc(path));
            cwd = norm(path);
            entries = d.entries || [];
            render();
        } catch (e) {
            toast(e.message, 'error');
        } finally {
            busy(false);
        }
    }

    function openEditor(path, content, readOnly, hint) {
        editPath = path;
        $('fm-edit-path').textContent = path;
        $('fm-edit-hint').textContent = hint || '';
        var ta = $('fm-edit-ta');
        ta.value = content;
        ta.readOnly = readOnly;
        $('fm-editor').hidden = false;
        $('fm-editor').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    async function edit(e) {
        if (e.size > LIMIT) { toast('File lebih dari 2 MB. Download aja.', 'error'); return; }
        busy(true);
        try {
            var path = join(cwd, e.name);
            var d = await req(U.contents + '?path=' + enc(path));
            openEditor(path, d.content, !WRITE, WRITE ? 'Ctrl+S buat simpan' : 'Hanya lihat (read-only)');
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            busy(false);
        }
    }

    async function save() {
        if (!WRITE || !editPath) return;
        var b = $('fm-save');
        b.disabled = true;
        try {
            await req(U.save + '?path=' + enc(editPath), {
                method: 'POST',
                headers: { 'Content-Type': 'text/plain; charset=utf-8' },
                body: $('fm-edit-ta').value
            });
            toast('Tersimpan.');
            await load(cwd);
        } catch (e) {
            toast(e.message, 'error');
        } finally {
            b.disabled = false;
        }
    }

    function newFile() {
        var n = (prompt('Nama file baru:') || '').trim();
        if (!n) return;
        if (!validName(n)) { toast('Nama nggak valid.', 'error'); return; }
        var ex = entries.filter(function (e) { return e.name === n; })[0];
        if (ex) {
            if (ex.is_dir) toast('Sudah ada folder dengan nama itu.', 'error'); else edit(ex);
            return;
        }
        openEditor(join(cwd, n), '', false, 'File baru, dibuat saat disimpan');
    }

    async function newDir() {
        var n = (prompt('Nama folder baru:') || '').trim();
        if (!n) return;
        if (!validName(n)) { toast('Nama nggak valid.', 'error'); return; }
        busy(true);
        try {
            await req(U.mkdir, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ path: join(cwd, n) }) });
            toast('Folder dibuat.');
        } catch (e) { toast(e.message, 'error'); }
        busy(false);
        await load(cwd);
    }

    async function extractArchive(e) {
        if (!confirm('Ekstrak ' + e.name + ' ke folder ini? File dengan nama sama akan ditimpa.')) return;
        busy(true);
        try {
            var d = await req(U.extract, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ path: join(cwd, e.name) }) });
            toast('Terekstrak' + (d && d.files != null ? ' (' + d.files + ' file).' : '.'));
        } catch (err) { toast(err.message, 'error'); }
        busy(false);
        await load(cwd);
    }

    async function rename(e) {
        var n = (prompt('Nama baru:', e.name) || '').trim();
        if (!n || n === e.name) return;
        if (!validName(n)) { toast('Nama nggak valid.', 'error'); return; }
        busy(true);
        try {
            await req(U.rename, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ from: join(cwd, e.name), to: join(cwd, n) }) });
            toast('Diganti nama.');
        } catch (err) { toast(err.message, 'error'); }
        busy(false);
        await load(cwd);
    }

    async function delNames(names) {
        if (!names.length) return;
        if (!confirm('Hapus ' + names.length + ' item? Aksi ini tidak bisa dibatalkan.')) return;
        busy(true);
        try {
            await req(U.del, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ paths: names.map(function (n) { return join(cwd, n); }) })
            });
            toast('Terhapus.');
        } catch (e) { toast(e.message, 'error'); }
        busy(false);
        await load(cwd);
    }

    async function upload(files) {
        if (!files.length) return;
        if (files.length > 20) { toast('Maksimal 20 file sekali upload.', 'error'); return; }
        var fd = new FormData();
        fd.append('path', cwd);
        for (var i = 0; i < files.length; i++) fd.append('files[]', files[i]);
        busy(true);
        toast('Mengupload ' + files.length + ' file...');
        try {
            var d = await req(U.upload, { method: 'POST', body: fd });
            toast('Terupload: ' + (d.uploaded || []).length + ' file.');
        } catch (e) { toast(e.message, 'error'); }
        busy(false);
        await load(cwd);
    }

    $('fm-refresh').addEventListener('click', function () { load(cwd); });
    $('fm-close').addEventListener('click', function () { $('fm-editor').hidden = true; editPath = null; });

    if (WRITE) {
        $('fm-save').addEventListener('click', save);
        $('fm-newfile').addEventListener('click', newFile);
        $('fm-newdir').addEventListener('click', newDir);
        $('fm-upload').addEventListener('click', function () { $('fm-file').click(); });
        $('fm-file').addEventListener('change', function (ev) {
            var f = Array.prototype.slice.call(ev.target.files);
            ev.target.value = '';
            upload(f);
        });
        $('fm-delsel').addEventListener('click', function () { delNames(Object.keys(selected)); });
        $('fm-all').addEventListener('change', function (ev) {
            var on = ev.target.checked;
            selected = {};
            document.querySelectorAll('#fm-body .fm-check').forEach(function (cb, i) { cb.checked = on; });
            if (on) entries.forEach(function (e) { selected[e.name] = true; });
            updateSel();
        });
        $('fm-edit-ta').addEventListener('keydown', function (ev) {
            if ((ev.ctrlKey || ev.metaKey) && ev.key.toLowerCase() === 's') { ev.preventDefault(); save(); }
        });
    }

    load('/');
})();
</script>
