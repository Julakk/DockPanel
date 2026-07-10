@extends('layouts.app')

@section('title', 'Servers - DockPanel')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="margin:0;">Servers</h2>
        <a href="{{ route('servers.create') }}" class="btn btn-primary">+ Buat Server</a>
    </div>

    <div class="card">
        @if ($servers->isEmpty())
            <p class="muted">Belum ada server. Pastikan udah ada Node dan Egg dulu sebelum bikin server.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Owner</th>
                        <th>Node</th>
                        <th>Egg</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($servers as $server)
                        <tr>
                            <td><a href="{{ route('servers.show', $server) }}" style="color:#f97316;text-decoration:none;">{{ $server->name }}</a></td>
                            <td class="muted">{{ $server->owner->name }}</td>
                            <td class="muted">{{ $server->node->name }}</td>
                            <td class="muted">{{ $server->egg->name }}</td>
                            <td>
                                @php
                                    $statusColor = match($server->status) {
                                        'running' => '#4ade80',
                                        'installing' => '#fbbf24',
                                        'install_failed', 'offline' => '#f87171',
                                        'suspended' => '#94a3b8',
                                        default => '#94a3b8',
                                    };
                                @endphp
                                <span style="color: {{ $statusColor }};">{{ $server->status }}</span>
                            </td>
                            <td class="actions">
                                <a href="{{ route('servers.edit', $server) }}" class="btn btn-secondary">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
