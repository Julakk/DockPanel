@extends('layouts.app')

@section('title', $server->name . ' - DockPanel')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="margin:0;">{{ $server->name }}</h2>
        <a href="{{ route('servers.edit', $server) }}" class="btn btn-secondary">Edit</a>
    </div>

    <div class="card">
        <table>
            <tr><th>Owner</th><td>{{ $server->owner->name }}</td></tr>
            <tr><th>Node</th><td>{{ $server->node->name }}</td></tr>
            <tr><th>Nest / Egg</th><td>{{ $server->egg->nest->name }} / {{ $server->egg->name }}</td></tr>
            <tr><th>Status</th><td>{{ $server->status }}</td></tr>
            <tr><th>Memory</th><td>{{ number_format($server->memory) }} MB</td></tr>
            <tr><th>Disk</th><td>{{ number_format($server->disk) }} MB</td></tr>
            <tr><th>CPU</th><td>{{ $server->cpu }}%</td></tr>
            <tr><th>Docker Image</th><td class="muted">{{ $server->image }}</td></tr>
            <tr>
                <th>Allocation</th>
                <td>
                    @forelse ($server->allocations as $alloc)
                        {{ $alloc->ip }}:{{ $alloc->port }} @if($alloc->is_primary) <span class="muted">(primary)</span> @endif<br>
                    @empty
                        <span class="muted">Belum ada allocation</span>
                    @endforelse
                </td>
            </tr>
        </table>
    </div>

    @if ($server->description)
        <div class="card">
            <p class="muted" style="margin:0;">{{ $server->description }}</p>
        </div>
    @endif

    <a href="{{ route('servers.index') }}" class="muted" style="text-decoration:none;">← Kembali ke daftar server</a>
@endsection
