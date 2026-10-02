@extends('layouts.app')

@section('title', 'Overview')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>Overview
@endsection

@section('content')
    <div class="pt-grid">
        <a href="{{ route('nodes.index') }}" class="pt-small pt-blue">
            <div class="pt-small-num">{{ $nodeCount }}</div><div class="pt-small-label">Nodes</div>
            <div class="pt-small-ico">@include('partials.icon', ['name' => 'server', 'size' => 64])</div>
            <span class="pt-small-more">Kelola</span>
        </a>
        <a href="{{ route('servers.index') }}" class="pt-small pt-green">
            <div class="pt-small-num">{{ $serverCount }}</div><div class="pt-small-label">Servers</div>
            <div class="pt-small-ico">@include('partials.icon', ['name' => 'package', 'size' => 64])</div>
            <span class="pt-small-more">Kelola</span>
        </a>
        <a href="{{ route('users.index') }}" class="pt-small pt-amber">
            <div class="pt-small-num">{{ $userCount }}</div><div class="pt-small-label">Users</div>
            <div class="pt-small-ico">@include('partials.icon', ['name' => 'users', 'size' => 64])</div>
            <span class="pt-small-more">Kelola</span>
        </a>
        <a href="{{ route('eggs.index') }}" class="pt-small pt-red">
            <div class="pt-small-num">{{ $eggCount }}</div><div class="pt-small-label">Eggs</div>
            <div class="pt-small-ico">@include('partials.icon', ['name' => 'egg', 'size' => 64])</div>
            <span class="pt-small-more">Kelola</span>
        </a>
    </div>

    <div class="pt-grid">
        <div class="card" style="text-align:center;margin:0;">
            <div class="muted" style="text-transform:uppercase;font-size:.72rem;">Running</div>
            <div style="font-size:1.6rem;font-weight:700;color:var(--green)">{{ $runningCount }}</div>
        </div>
        <div class="card" style="text-align:center;margin:0;">
            <div class="muted" style="text-transform:uppercase;font-size:.72rem;">Installing</div>
            <div style="font-size:1.6rem;font-weight:700;color:var(--amber)">{{ $installingCount }}</div>
        </div>
        <div class="card" style="text-align:center;margin:0;">
            <div class="muted" style="text-transform:uppercase;font-size:.72rem;">Suspended</div>
            <div style="font-size:1.6rem;font-weight:700;color:var(--gray)">{{ $suspendedCount }}</div>
        </div>
        <div class="card" style="text-align:center;margin:0;">
            <div class="muted" style="text-transform:uppercase;font-size:.72rem;">Expired / &lt;7 hari</div>
            <div style="font-size:1.6rem;font-weight:700;color:var(--red)">{{ $expiringCount }}</div>
        </div>
    </div>

    <div class="card" style="margin-top:1.25rem;">
        <h3>System Information</h3>
        <p class="muted" style="margin:0;">Login sebagai <strong>{{ $user->name }}</strong> &middot; DockPanel <code>v{{ config('app.version') }}</code> &middot; <a href="https://github.com/Julakk/DockPanel" target="_blank" rel="noopener">GitHub</a></p>
    </div>

    <div class="card">
        <h3>Nodes</h3>
        @if ($nodes->isEmpty())
            <p class="muted" style="margin:0;">Belum ada node. <a href="{{ route('nodes.create') }}">Buat node pertama</a>.</p>
        @else
            <div class="table-wrap">
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
            </div>
        @endif
    </div>

    <div class="row">
        <div class="card" style="flex:1; min-width:280px;">
            <h3>Server terbaru</h3>
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
            <h3>Aktivitas terbaru</h3>
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
@endsection
