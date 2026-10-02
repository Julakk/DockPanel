@extends('layouts.app')

@section('title', 'Servers - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>Servers
@endsection

@section('content')
    <h2 class="nd-title">Servers <small>All servers available on the system.</small></h2>

    <div class="nd-box">
        <div class="nd-box-head">
            <h3>Server List <span class="nd-mute" style="font-weight:400;font-size:.8rem;">({{ $servers->total() }})</span></h3>
            <form method="GET" action="{{ route('servers.index') }}" class="nd-tools">
                <select name="status" class="nd-sel" onchange="this.form.submit()">
                    <option value="">All status</option>
                    @foreach (['running', 'installing', 'offline', 'suspended', 'install_failed'] as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ $st }}</option>
                    @endforeach
                </select>
                <select name="node" class="nd-sel" onchange="this.form.submit()">
                    <option value="">All nodes</option>
                    @foreach ($nodes as $n)
                        <option value="{{ $n->id }}" @selected((string) request('node') === (string) $n->id)>{{ $n->name }}</option>
                    @endforeach
                </select>
                <div class="nd-search">
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search Servers" autocomplete="off">
                    <button type="submit" class="nd-search-btn" aria-label="Search"><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="10" cy="10" r="6"/><path d="M15 15l6 6"/></svg></button>
                </div>
                <a href="{{ route('servers.create') }}" class="nd-btn nd-btn-blue">Create New</a>
            </form>
        </div>

        <div class="nd-box-body nd-flush">
            @if ($servers->isEmpty())
                <div class="empty-state">
                    <div class="icon">@include('partials.icon', ['name' => 'package', 'size' => 40])</div>
                    <p>Tidak ada server yang cocok. Pastikan udah ada Node dan Egg sebelum bikin server.</p>
                </div>
            @else
                <div class="nd-wrap">
                    <table class="nd-table">
                        <thead>
                            <tr>
                                <th>Server Name</th>
                                <th>UUID</th>
                                <th>Owner</th>
                                <th>Node</th>
                                <th>Connection</th>
                                <th></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($servers as $server)
                                @php($alloc = $server->primaryAllocation)
                                <tr>
                                    <td><a href="{{ route('servers.show', $server) }}">{{ $server->name }}</a></td>
                                    <td><span class="nd-code nd-uuid">{{ $server->uuid ?? $server->uuid_short }}</span></td>
                                    <td>
                                        @if ($server->owner)
                                            <a href="{{ route('users.edit', $server->owner) }}">{{ $server->owner->name }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if ($server->node)
                                            <a href="{{ route('nodes.show', $server->node) }}">{{ $server->node->name }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="nd-nowrap">
                                        @if ($alloc)
                                            <span class="nd-code">{{ $alloc->ip }}:{{ $alloc->port }}</span>
                                        @else
                                            <span class="nd-mute">-</span>
                                        @endif
                                    </td>
                                    <td><span class="status-badge status-{{ $server->suspended ? 'suspended' : $server->status }}">{{ $server->suspended ? 'suspended' : $server->status }}</span></td>
                                    <td>
                                        <a href="{{ route('servers.edit', $server) }}" class="nd-iconbtn" title="Manage"><svg viewBox="0 0 24 24" width="13" height="13" fill="currentColor"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.1L3 17.7 6.3 21l6.3-6.3a4 4 0 0 0 5.1-5.4l-2.6 2.6-2.4-.6-.6-2.4z"/></svg></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($servers->hasPages())
                    <div class="nd-foot">
                        @if ($servers->onFirstPage())<span class="nd-mute">&larr; Sebelumnya</span>@else<a class="nd-btn nd-btn-blue" href="{{ $servers->previousPageUrl() }}">&larr; Sebelumnya</a>@endif
                        <span class="nd-mute">Halaman {{ $servers->currentPage() }} / {{ $servers->lastPage() }}</span>
                        @if ($servers->hasMorePages())<a class="nd-btn nd-btn-blue" href="{{ $servers->nextPageUrl() }}">Berikutnya &rarr;</a>@else<span class="nd-mute">Berikutnya &rarr;</span>@endif
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection
