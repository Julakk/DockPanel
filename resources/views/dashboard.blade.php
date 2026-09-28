@extends('layouts.app')

@section('title', 'Overview')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>Overview
@endsection

@section('content')
    <h2 style="margin-top:0;">Administrative Overview</h2>
    <p class="muted" style="margin-top:-0.6rem;">A quick glance at your system, {{ $user->name }}. DockPanel <code>v{{ config('app.version') }}</code></p>

    <div class="row">
        @foreach ([
            ['Nodes', $nodeCount], ['Servers', $serverCount], ['Users', $userCount], ['Eggs', $eggCount],
        ] as [$label, $value])
            <div class="card" style="flex:1; min-width:130px; text-align:center;">
                <div class="muted" style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em;">{{ $label }}</div>
                <div style="font-size:1.8rem; font-weight:700; margin-top:0.3rem;">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="card" style="flex:1; min-width:130px; text-align:center;">
            <div class="muted" style="font-size:0.75rem;text-transform:uppercase;">Running</div>
            <div style="font-size:1.4rem;font-weight:700;color:var(--green)">{{ $runningCount }}</div>
        </div>
        <div class="card" style="flex:1; min-width:130px; text-align:center;">
            <div class="muted" style="font-size:0.75rem;text-transform:uppercase;">Installing</div>
            <div style="font-size:1.4rem;font-weight:700;color:var(--amber)">{{ $installingCount }}</div>
        </div>
        <div class="card" style="flex:1; min-width:130px; text-align:center;">
            <div class="muted" style="font-size:0.75rem;text-transform:uppercase;">Suspended</div>
            <div style="font-size:1.4rem;font-weight:700;color:var(--gray)">{{ $suspendedCount }}</div>
        </div>
        <div class="card" style="flex:1; min-width:130px; text-align:center;">
            <div class="muted" style="font-size:0.75rem;text-transform:uppercase;">Expired / &lt;7 hari</div>
            <div style="font-size:1.4rem;font-weight:700;color:var(--red)">{{ $expiringCount }}</div>
        </div>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Nodes</h3>
        @if ($nodes->isEmpty())
            <p class="muted" style="margin:0;">Belum ada node. <a href="{{ route('nodes.create') }}">Buat node pertama</a>.</p>
        @else
            <table>
                <thead><tr><th>Node</th><th>Server</th><th>Memory</th><th>Disk</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach ($nodes as $n)
                        @php($mp = $n->memory > 0 ? min(100, $n->memoryUsed() / $n->memory * 100) : 0)
                        @php($dp = $n->disk > 0 ? min(100, $n->diskUsed() / $n->disk * 100) : 0)
                        <tr>
                            <td><a href="{{ route('nodes.show', $n) }}">{{ $n->name }}</a><div class="muted">{{ $n->fqdn }}</div></td>
                            <td>{{ $n->servers_count }}</td>
                            <td style="min-width:110px"><div class="stat-bar"><div class="stat-bar-fill" style="width:{{ $mp }}%"></div></div><div class="stat-value">{{ $n->memoryUsed() }} / {{ number_format($n->memory) }} MB</div></td>
                            <td style="min-width:110px"><div class="stat-bar"><div class="stat-bar-fill" style="width:{{ $dp }}%"></div></div><div class="stat-value">{{ $n->diskUsed() }} / {{ number_format($n->disk) }} MB</div></td>
                            <td>@if ($n->maintenance_mode)<span class="status-badge status-maintenance">Maintenance</span>@else<span class="status-badge status-active">Aktif</span>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="row">
        <div class="card" style="flex:1; min-width:280px;">
            <h3 style="margin-top:0;">Server terbaru</h3>
            @forelse ($recentServers as $s)
                <div style="display:flex;justify-content:space-between;gap:.5rem;padding:.4rem 0;border-bottom:1px solid var(--border)">
                    <a href="{{ route('servers.show', $s) }}">{{ $s->name }}</a>
                    <span class="muted">{{ $s->owner->name ?? '-' }} · {{ $s->node->name ?? '-' }}</span>
                </div>
            @empty
                <p class="muted" style="margin:0;">Belum ada server.</p>
            @endforelse
        </div>
        <div class="card" style="flex:1; min-width:280px;">
            <h3 style="margin-top:0;">Aktivitas terbaru</h3>
            @forelse ($recentActivity as $a)
                <div style="display:flex;justify-content:space-between;gap:.5rem;padding:.4rem 0;border-bottom:1px solid var(--border)">
                    <span><code>{{ $a->event }}</code> <span class="muted">{{ $a->user->name ?? '' }}</span></span>
                    <span class="muted">{{ $a->created_at?->diffForHumans() }}</span>
                </div>
            @empty
                <p class="muted" style="margin:0;">Belum ada aktivitas.</p>
            @endforelse
        </div>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Quick Links</h3>
        <div style="display:flex; gap:0.6rem; flex-wrap:wrap;">
            <a href="{{ route('nodes.index') }}" class="btn btn-primary">@include('partials.icon', ['name' => 'server', 'size' => 16]) Kelola Nodes</a>
            <a href="{{ route('servers.index') }}" class="btn btn-secondary">@include('partials.icon', ['name' => 'package', 'size' => 16]) Kelola Servers</a>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">@include('partials.icon', ['name' => 'users', 'size' => 16]) Kelola Users</a>
            <a href="{{ route('eggs.index') }}" class="btn btn-secondary">@include('partials.icon', ['name' => 'egg', 'size' => 16]) Kelola Eggs</a>
        </div>
    </div>
@endsection
