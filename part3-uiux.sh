#!/usr/bin/env bash
# DockPanel - PART 3: UI/UX (toast, modal konfirmasi, tabel responsif, loading state, halaman error)
set -e
[ -f artisan ] || { echo "Jalankan dari root repo DockPanel (yang ada file artisan)"; exit 1; }
mkdir -p public/js resources/views/errors

cat > public/js/dp-ui.js << 'EOF'
(function () {
  'use strict';

  function host() {
    var h = document.getElementById('dp-toasts');
    if (!h) { h = document.createElement('div'); h.id = 'dp-toasts'; document.body.appendChild(h); }
    return h;
  }

  function toast(text, type, sticky) {
    var t = document.createElement('div');
    t.className = 'dp-toast dp-toast-' + (type || 'success');
    t.setAttribute('role', 'status');
    var s = document.createElement('span');
    s.textContent = text;
    t.appendChild(s);
    var closed = false;
    function close() {
      if (closed) return;
      closed = true;
      t.classList.add('out');
      setTimeout(function () { t.remove(); }, 250);
    }
    var x = document.createElement('button');
    x.type = 'button';
    x.setAttribute('aria-label', 'Tutup');
    x.textContent = '\u00d7';
    x.addEventListener('click', close);
    t.appendChild(x);
    host().appendChild(t);
    if (!sticky) setTimeout(close, type === 'error' ? 9000 : 5000);
  }
  window.dpToast = toast;

  function convertFlash() {
    document.querySelectorAll('.success, .dp-ok, .dp-err').forEach(function (el) {
      if (el.closest('#dp-toasts') || el.closest('.dp-modal')) return;
      var txt = el.textContent.trim();
      if (!txt) return;
      // Pesan penting (mis. token node yang cuma tampil sekali) jangan diubah jadi toast otomatis hilang
      if (/token/i.test(txt) || txt.length > 140) return;
      toast(txt, el.classList.contains('dp-err') ? 'error' : 'success', false);
      el.remove();
    });
  }

  function confirmDialog(message) {
    return new Promise(function (resolve) {
      var prev = document.activeElement;
      var ov = document.createElement('div');
      ov.className = 'dp-modal';
      var box = document.createElement('div');
      box.className = 'dp-modal-box';
      box.setAttribute('role', 'alertdialog');
      box.setAttribute('aria-modal', 'true');
      var p = document.createElement('p');
      p.textContent = message;
      var row = document.createElement('div');
      row.className = 'dp-modal-actions';
      var no = document.createElement('button');
      no.type = 'button'; no.className = 'btn btn-secondary'; no.textContent = 'Batal';
      var yes = document.createElement('button');
      yes.type = 'button'; yes.className = 'btn btn-primary'; yes.textContent = 'Ya, lanjutkan';
      row.appendChild(no); row.appendChild(yes);
      box.appendChild(p); box.appendChild(row);
      ov.appendChild(box);
      document.body.appendChild(ov);
      yes.focus();

      function done(v) {
        document.removeEventListener('keydown', onKey, true);
        ov.remove();
        if (prev && prev.focus) prev.focus();
        resolve(v);
      }
      function onKey(e) { if (e.key === 'Escape') { e.preventDefault(); done(false); } }
      document.addEventListener('keydown', onKey, true);
      no.addEventListener('click', function () { done(false); });
      yes.addEventListener('click', function () { done(true); });
      ov.addEventListener('mousedown', function (e) { if (e.target === ov) done(false); });
    });
  }

  function startLoading(form) {
    setTimeout(function () {
      form.querySelectorAll('button[type=submit], button:not([type]), input[type=submit]').forEach(function (b) {
        b.disabled = true;
        b.classList.add('is-loading');
      });
    }, 0);
  }

  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!(f instanceof HTMLFormElement)) return;

    if (f.dataset.dpOk === '1') { f.dataset.dpOk = ''; startLoading(f); return; }
    if (f.getAttribute('onsubmit')) return;

    var msg = f.dataset.confirm;
    if (!msg) {
      var m = f.querySelector('input[name="_method"]');
      if (m && m.value.toUpperCase() === 'DELETE') msg = 'Yakin mau hapus? Aksi ini tidak bisa dibatalkan.';
    }
    if (!msg) { startLoading(f); return; }

    e.preventDefault();
    var submitter = e.submitter;
    confirmDialog(msg).then(function (ok) {
      if (!ok) return;
      f.dataset.dpOk = '1';
      if (f.requestSubmit) f.requestSubmit(submitter || undefined); else f.submit();
    });
  }, true);

  window.addEventListener('pageshow', function () {
    document.querySelectorAll('.is-loading').forEach(function (b) {
      b.disabled = false;
      b.classList.remove('is-loading');
    });
  });

  function tables() {
    document.querySelectorAll('table').forEach(function (t) {
      if (t.closest('.table-wrap')) return;
      var w = document.createElement('div');
      w.className = 'table-wrap';
      t.parentNode.insertBefore(w, t);
      w.appendChild(t);

      var rows = t.querySelectorAll('tbody tr');
      if (rows.length >= 8 && t.querySelector('thead')) {
        var i = document.createElement('input');
        i.type = 'search';
        i.placeholder = 'Filter tabel...';
        i.className = 'dp-table-filter';
        w.parentNode.insertBefore(i, w);
        i.addEventListener('input', function () {
          var q = i.value.toLowerCase();
          rows.forEach(function (r) { r.style.display = r.textContent.toLowerCase().indexOf(q) === -1 ? 'none' : ''; });
        });
      }
    });
  }

  function init() { convertFlash(); tables(); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
EOF

if ! grep -q "dp-ui v1" public/css/dp-theme.css; then
cat >> public/css/dp-theme.css << 'EOF'

/* ── dp-ui v1: toast, modal, tabel responsif, loading, a11y ── */
#dp-toasts{position:fixed;top:1rem;right:1rem;z-index:1000;display:flex;flex-direction:column;gap:.5rem;
  max-width:min(380px,calc(100vw - 2rem))}
.dp-toast{display:flex;align-items:flex-start;gap:.75rem;justify-content:space-between;
  background:var(--surface);border:1px solid var(--border);border-left:4px solid var(--green);
  border-radius:var(--radius);padding:.7rem .9rem;box-shadow:var(--shadow-md);font-size:.88rem;
  animation:dp-in .2s ease-out}
.dp-toast-error{border-left-color:var(--red)}
.dp-toast button{background:none;border:0;color:var(--text-muted);font-size:1.2rem;line-height:1;cursor:pointer;padding:0}
.dp-toast button:hover{color:#fff}
.dp-toast.out{opacity:0;transform:translateY(-6px);transition:opacity .25s,transform .25s}
@keyframes dp-in{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:none}}
@media (max-width:600px){#dp-toasts{top:auto;bottom:1rem;right:1rem;left:1rem;max-width:none}}

.dp-modal{position:fixed;inset:0;z-index:1100;background:rgba(0,0,0,.6);display:flex;
  align-items:center;justify-content:center;padding:1rem;animation:dp-fade .15s ease-out}
.dp-modal-box{background:var(--surface);border:1px solid var(--border);border-top:3px solid var(--accent);
  border-radius:var(--radius-lg);padding:1.25rem;width:100%;max-width:420px;box-shadow:var(--shadow-md)}
.dp-modal-box p{margin:0 0 1.1rem;line-height:1.5}
.dp-modal-actions{display:flex;justify-content:flex-end;gap:.5rem;flex-wrap:wrap}
@keyframes dp-fade{from{opacity:0}to{opacity:1}}

.table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
@media (max-width:700px){.table-wrap table{min-width:520px}}
.dp-table-filter{max-width:280px;margin-bottom:.6rem!important}

.btn.is-loading,button.is-loading{opacity:.6;cursor:wait}
:focus-visible{outline:2px solid var(--accent);outline-offset:2px}
main{animation:dp-fade .25s ease-out}
@media (prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}}
EOF
fi

cat > resources/views/errors/dp.blade.php << 'EOF'
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
EOF

mkerr() {
cat > "resources/views/errors/$1.blade.php" << EOF
@extends('errors.dp')
@section('code', '$1')
@section('heading', '$2')
@section('message', '$3')
EOF
}
mkerr 403 "Akses ditolak" "Kamu nggak punya izin buat buka halaman ini."
mkerr 404 "Halaman nggak ketemu" "Halaman yang kamu cari nggak ada atau sudah dipindah."
mkerr 419 "Sesi kedaluwarsa" "Refresh halaman lalu coba lagi."
mkerr 429 "Terlalu banyak request" "Tunggu sebentar, lalu coba lagi."
mkerr 500 "Ada yang error di server" "Coba lagi nanti. Kalau terus muncul, cek storage/logs/laravel.log."
mkerr 503 "Lagi maintenance" "Panel lagi diperbarui, coba lagi sebentar."

for f in resources/views/layouts/app.blade.php resources/views/layouts/client.blade.php; do
  if ! grep -q "js/dp-ui.js" "$f"; then
    sed -i 's|</body>|<script src="{{ asset("js/dp-ui.js") }}?v={{ @filemtime(public_path("js/dp-ui.js")) }}" defer></script>\n</body>|' "$f"
  fi
  grep -q "js/dp-ui.js" "$f" || echo "PERINGATAN: gagal pasang dp-ui.js di $f (tambah manual sebelum </body>)"
done

php artisan view:clear >/dev/null 2>&1 || true
php artisan route:clear >/dev/null 2>&1 || true
echo "PART 3 selesai."
