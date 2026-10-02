@extends('layouts.app')

@section('title', 'Nodes - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>Nodes
@endsection

@section('content')
    <h2 class="nd-title">Nodes <small>All nodes available on the system.</small></h2>

    <div class="nd-box">
        <div class="nd-box-head">
            <h3>Node List</h3>
            <div class="nd-tools">
                <div class="nd-search">
                    <input type="text" id="nd-search" placeholder="Search Nodes" autocomplete="off">
                    <span class="nd-search-btn"><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="10" cy="10" r="6"/><path d="M15 15l6 6"/></svg></span>
                </div>
                <a href="{{ route('nodes.create') }}" class="nd-btn nd-btn-blue">Create New</a>
            </div>
        </div>
        <div class="nd-box-body nd-flush">
            @if ($nodes->isEmpty())
                <div class="empty-state">
                    <div class="icon">@include('partials.icon', ['name' => 'server', 'size' => 40])</div>
                    <p>Belum ada node. Tambah node pertama buat mulai kelola VPS.</p>
                </div>
            @else
                <table class="nd-table" id="nd-table">
                    <thead>
                        <tr>
                            <th style="width:30px"></th>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Memory</th>
                            <th>Disk</th>
                            <th class="c">Servers</th>
                            <th class="c">SSL</th>
                            <th class="c">Public</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($nodes as $node)
                            <tr data-name="{{ strtolower($node->name) }}">
                                <td class="c">
                                    <span class="nd-heart nd-heart-wait" data-status="{{ route('nodes.status', $node) }}" title="Checking...">
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M12 21s-7.5-4.6-9.6-9.2C.9 8.3 3 4.5 6.6 4.5c2 0 3.5 1 5.4 3 1.9-2 3.4-3 5.4-3 3.6 0 5.7 3.8 4.2 7.3C19.5 16.4 12 21 12 21z"/></svg>
                                    </span>
                                </td>
                                <td><a href="{{ route('nodes.show', $node) }}">{{ $node->name }}</a></td>
                                <td>{{ $node->location?->short_code ?? '-' }}</td>
                                <td>{{ $node->memory }} MiB</td>
                                <td>{{ $node->disk }} MiB</td>
                                <td class="c">{{ $node->servers_count }}</td>
                                <td class="c">
                                    @if ($node->scheme === 'https')
                                        <span class="nd-on" title="HTTPS"><svg viewBox="0 0 24 24" width="13" height="13" fill="currentColor"><path d="M6 10V8a6 6 0 0 1 12 0v2h1a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V11a1 1 0 0 1 1-1h1zm2 0h8V8a4 4 0 0 0-8 0v2z"/></svg></span>
                                    @else
                                        <span class="nd-off" title="HTTP (tanpa SSL)"><svg viewBox="0 0 24 24" width="13" height="13" fill="currentColor"><path d="M17 10V8a5 5 0 0 0-9.6-2l1.9.7A3 3 0 0 1 15 8v2H5a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V11a1 1 0 0 0-1-1h-2z"/></svg></span>
                                    @endif
                                </td>
                                <td class="c">
                                    @if ($node->public)
                                        <span class="nd-mute" title="Public"><svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M12 5C6.5 5 2.2 8.6 1 12c1.2 3.4 5.5 7 11 7s9.8-3.6 11-7c-1.2-3.4-5.5-7-11-7zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm0-2a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg></span>
                                    @else
                                        <span class="nd-mute" title="Private"><svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M12 5C6.5 5 2.2 8.6 1 12c1.2 3.4 5.5 7 11 7s9.8-3.6 11-7c-1.2-3.4-5.5-7-11-7zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8z"/><path d="M3 3l18 18" stroke="currentColor" stroke-width="2"/></svg></span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <script>
        (function () {
            var q = document.getElementById('nd-search');
            if (q) {
                q.addEventListener('input', function () {
                    var v = q.value.toLowerCase();
                    document.querySelectorAll('#nd-table tbody tr').forEach(function (r) {
                        r.style.display = (r.dataset.name || '').indexOf(v) > -1 ? '' : 'none';
                    });
                });
            }
            document.querySelectorAll('.nd-heart').forEach(function (el) {
                fetch(el.dataset.status, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        el.classList.remove('nd-heart-wait');
                        el.classList.add(d.ok ? 'nd-heart-ok' : 'nd-heart-down');
                        el.title = d.ok ? 'Daemon online' : (d.error || 'Daemon offline');
                    })
                    .catch(function () {
                        el.classList.remove('nd-heart-wait');
                        el.classList.add('nd-heart-down');
                        el.title = 'Gagal cek status daemon';
                    });
            });
        })();
    </script>
@endsection
