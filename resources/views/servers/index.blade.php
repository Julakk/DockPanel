@extends('layouts.app')

@section('title', 'Servers - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>Servers
@endsection

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; gap:.5rem; flex-wrap:wrap;">
        <h2 style="margin:0;">Servers <span class="muted">({{ $servers->total() }})</span></h2>
        <a href="{{ route('servers.create') }}" class="btn btn-primary">+ Buat Server</a>
    </div>

    <form method="GET" action="{{ route('servers.index') }}" class="card" style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:end;padding:1rem;">
        <div style="flex:2;min-width:180px"><label>Cari (nama / ID / owner)</label><input type="search" name="q" value="{{ request('q') }}" style="margin:0"></div>
        <div style="flex:1;min-width:130px"><label>Status</label>
            <select name="status" style="margin:0">
                <option value="">Semua</option>
                @foreach (['running','installing','offline','suspended','install_failed'] as $st)
                    <option value="{{ $st }}" @selected(request('status') === $st)>{{ $st }}</option>
                @endforeach
            </select>
        </div>
        <div style="flex:1;min-width:130px"><label>Node</label>
            <select name="node" style="margin:0">
                <option value="">Semua</option>
                @foreach ($nodes as $n)
                    <option value="{{ $n->id }}" @selected((string) request('node') === (string) $n->id)>{{ $n->name }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-primary" type="submit">Filter</button>
        @if (request()->hasAny(['q','status','node']))<a href="{{ route('servers.index') }}" class="btn btn-secondary">Reset</a>@endif
    </form>

    @if ($servers->isEmpty())
        <div class="card">
            <div class="empty-state">
                <div class="icon">@include('partials.icon', ['name' => 'package', 'size' => 40])</div>
                <p>Tidak ada server yang cocok. Pastikan udah ada Node dan Egg sebelum bikin server.</p>
                <a href="{{ route('servers.create') }}" class="btn btn-primary">+ Buat Server</a>
            </div>
        </div>
    @else
        @foreach ($servers as $server)
            <div class="server-card status-{{ $server->status }}-border">
                <div class="server-card-icon">
                    @include('partials.icon', ['name' => 'package', 'size' => 18])
                </div>

                <div>
                    <div class="server-card-name">
                        <a href="{{ route('servers.show', $server) }}">{{ $server->name }}</a>
                        <span class="muted">#{{ $server->uuid_short }}</span>
                    </div>
                    <div class="server-card-sub">{{ $server->owner->name }} — {{ $server->node->name }} / {{ $server->egg->name }}</div>
                </div>

                <span class="status-badge status-{{ $server->suspended ? 'suspended' : $server->status }}" style="margin-left:0.5rem;">{{ $server->suspended ? 'suspended' : $server->status }}</span>

                <div class="server-card-stats">
                    <div class="stat"><div class="stat-label">Memory</div><div class="stat-value">{{ $server->memory ? $server->memory.' MB' : '∞' }}</div></div>
                    <div class="stat"><div class="stat-label">Disk</div><div class="stat-value">{{ $server->disk ? $server->disk.' MB' : '∞' }}</div></div>
                    <div class="stat"><div class="stat-label">CPU</div><div class="stat-value">{{ $server->cpu ? $server->cpu.'%' : '∞' }}</div></div>
                </div>

                <a href="{{ route('servers.edit', $server) }}" class="btn btn-secondary" style="margin-left:0.5rem;">Edit</a>
            </div>
        @endforeach

        @if ($servers->hasPages())
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:1rem;">
                @if ($servers->onFirstPage())<span class="muted">&larr; Sebelumnya</span>@else<a class="btn btn-secondary" href="{{ $servers->previousPageUrl() }}">&larr; Sebelumnya</a>@endif
                <span class="muted">Halaman {{ $servers->currentPage() }} / {{ $servers->lastPage() }}</span>
                @if ($servers->hasMorePages())<a class="btn btn-secondary" href="{{ $servers->nextPageUrl() }}">Berikutnya &rarr;</a>@else<span class="muted">Berikutnya &rarr;</span>@endif
            </div>
        @endif
    @endif
@endsection
