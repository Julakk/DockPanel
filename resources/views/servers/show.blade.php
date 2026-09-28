@extends('layouts.app')

@section('title', $server->name . ' - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>
    <a href="{{ route('servers.index') }}">Servers</a><span class="sep">&gt;</span>{{ $server->name }}
@endsection

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; gap:.5rem; flex-wrap:wrap;">
        <h2 style="margin:0;">{{ $server->name }} <span class="status-badge status-{{ $server->suspended ? 'suspended' : $server->status }}">{{ $server->suspended ? 'suspended' : $server->status }}</span></h2>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
            <a href="{{ route('client.servers.show', $server) }}" class="btn btn-secondary">Buka sebagai user</a>
            <a href="{{ route('servers.edit', $server) }}" class="btn btn-primary">Edit (Details, Build, Startup, DB, Mounts)</a>
        </div>
    </div>

    @if ($errors->any())<div class="error">{{ $errors->first() }}</div>@endif

    <div class="row">
        <div class="card" style="flex:1;min-width:280px;">
            <h3 style="margin-top:0;">About</h3>
            <table>
                <tr><th>ID</th><td><code>{{ $server->uuid_short }}</code></td></tr>
                <tr><th>UUID</th><td class="muted">{{ $server->uuid }}</td></tr>
                <tr><th>Owner</th><td>{{ $server->owner->name }} <span class="muted">{{ $server->owner->email }}</span></td></tr>
                <tr><th>Node</th><td><a href="{{ route('nodes.show', $server->node) }}">{{ $server->node->name }}</a></td></tr>
                <tr><th>Nest / Egg</th><td>{{ $server->egg->nest->name }} / {{ $server->egg->name }}</td></tr>
                <tr><th>Docker Image</th><td class="muted">{{ $server->image }}</td></tr>
                @if ($server->expires_at)<tr><th>Expired</th><td>{{ $server->expires_at->format('d M Y H:i') }} <span class="muted">({{ $server->expires_at->diffForHumans() }})</span></td></tr>@endif
            </table>
        </div>
        <div class="card" style="flex:1;min-width:280px;">
            <h3 style="margin-top:0;">Build Configuration</h3>
            <table>
                <tr><th>Memory</th><td>{{ $server->memory ? number_format($server->memory).' MB' : 'Unlimited' }}</td></tr>
                <tr><th>Swap</th><td>{{ $server->swap }} MB</td></tr>
                <tr><th>Disk</th><td>{{ $server->disk ? number_format($server->disk).' MB' : 'Unlimited' }}</td></tr>
                <tr><th>CPU</th><td>{{ $server->cpu ? $server->cpu.'%' : 'Unlimited' }}</td></tr>
                <tr><th>Block IO</th><td>{{ $server->io }}</td></tr>
            </table>
        </div>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Allocations</h3>
        <table>
            <thead><tr><th>IP</th><th>Port</th><th></th></tr></thead>
            <tbody>
                @forelse ($server->allocations as $alloc)
                    <tr><td>{{ $alloc->ip }}</td><td>{{ $alloc->port }}</td><td>@if ($alloc->is_primary)<span class="status-badge status-active">Primary</span>@endif</td></tr>
                @empty
                    <tr><td colspan="3" class="muted">Belum ada allocation.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="row">
        <div class="card" style="flex:1;min-width:280px;">
            <h3 style="margin-top:0;">Startup</h3>
            <div class="muted">Command</div>
            <code style="display:block;white-space:pre-wrap;margin-bottom:.8rem;">{{ $server->startup }}</code>
            @foreach ($server->serverVariables as $sv)
                <div style="display:flex;justify-content:space-between;gap:.5rem;padding:.3rem 0;border-bottom:1px solid var(--border)">
                    <span>{{ $sv->eggVariable->name ?? '-' }} <code>{{ $sv->eggVariable->env_variable ?? '' }}</code></span>
                    <span class="muted">{{ $sv->variable_value }}</span>
                </div>
            @endforeach
        </div>
        <div class="card" style="flex:1;min-width:280px;">
            <h3 style="margin-top:0;">Databases, Mounts &amp; Subusers</h3>
            <div class="muted">Databases</div>
            @forelse ($server->databases as $db)<div><code>{{ $db->database }}</code> <span class="muted">@ {{ $db->databaseHost->name ?? '-' }}</span></div>@empty<div class="muted">-</div>@endforelse
            <div class="muted" style="margin-top:.6rem;">Mounts</div>
            @forelse ($server->mounts as $m)<div>{{ $m->name }}</div>@empty<div class="muted">-</div>@endforelse
            <div class="muted" style="margin-top:.6rem;">Subusers</div>
            @forelse ($server->subusers as $u)<div>{{ $u->name }} <span class="muted">{{ $u->email }}</span></div>@empty<div class="muted">-</div>@endforelse
        </div>
    </div>

    <div class="card" style="border-top-color:var(--red)!important;">
        <h3 style="margin-top:0;">Manage</h3>
        <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
            <form method="POST" action="{{ route('servers.provision', $server) }}" data-confirm="Provision server ke Wings sekarang?">
                @csrf <button class="btn btn-secondary" type="submit">Provision ke Wings</button>
            </form>
            @if ($server->suspended)
                <form method="POST" action="{{ route('servers.unsuspend', $server) }}">
                    @csrf <button class="btn btn-primary" type="submit">Unsuspend</button>
                </form>
            @else
                <form method="POST" action="{{ route('servers.suspend', $server) }}" data-confirm="Suspend server '{{ $server->name }}'? Server akan dihentikan.">
                    @csrf <button class="btn btn-secondary" type="submit">Suspend</button>
                </form>
            @endif
            <form method="POST" action="{{ route('servers.destroy', $server) }}" data-confirm="Hapus server '{{ $server->name }}' permanen?">
                @csrf @method('DELETE') <button class="btn btn-danger" type="submit">Hapus Server</button>
            </form>
        </div>
    </div>
@endsection
