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
