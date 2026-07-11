@extends('layouts.app')

@section('title', 'Servers - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>Servers
@endsection

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="margin:0;">Servers</h2>
        <a href="{{ route('servers.create') }}" class="btn btn-primary">+ Buat Server</a>
    </div>

    @if ($servers->isEmpty())
        <div class="card">
            <div class="empty-state">
                <div class="icon">@include('partials.icon', ['name' => 'package', 'size' => 40])</div>
                <p>Belum ada server. Pastikan udah ada Node dan Egg dulu sebelum bikin server.</p>
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
                    </div>
                    <div class="server-card-sub">{{ $server->owner->name }} — {{ $server->node->name }} / {{ $server->egg->name }}</div>
                </div>

                <span class="status-badge status-{{ $server->status }}" style="margin-left:0.5rem;">{{ $server->status }}</span>

                {{-- Resource usage: placeholder sampai Wings aktif dan bisa lapor data beneran --}}
                <div class="server-card-stats">
                    <div class="stat">
                        <div class="stat-label">CPU</div>
                        <div class="stat-bar"><div class="stat-bar-fill" style="width:0%;"></div></div>
                        <div class="stat-value">—</div>
                    </div>
                    <div class="stat">
                        <div class="stat-label">Memory</div>
                        <div class="stat-bar"><div class="stat-bar-fill" style="width:0%;"></div></div>
                        <div class="stat-value">—</div>
                    </div>
                    <div class="stat">
                        <div class="stat-label">Disk</div>
                        <div class="stat-bar"><div class="stat-bar-fill" style="width:0%;"></div></div>
                        <div class="stat-value">—</div>
                    </div>
                </div>

                <a href="{{ route('servers.edit', $server) }}" class="btn btn-secondary" style="margin-left:0.5rem;">Edit</a>
            </div>
        @endforeach
    @endif
@endsection
