@extends('layouts.app')

@section('title', 'Nodes - DockPanel')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Dashboard</a><span class="sep">/</span>Nodes
@endsection

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="margin:0;">Nodes</h2>
        <a href="{{ route('nodes.create') }}" class="btn btn-primary">+ Tambah Node</a>
    </div>

    <div class="card">
        @if ($nodes->isEmpty())
            <div class="empty-state">
                <div class="icon">🖥️</div>
                <p>Belum ada node. Tambah node pertama buat mulai kelola VPS.</p>
                <a href="{{ route('nodes.create') }}" class="btn btn-primary">+ Tambah Node</a>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>FQDN</th>
                        <th>Memory</th>
                        <th>Disk</th>
                        <th>Server</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($nodes as $node)
                        <tr>
                            <td><a href="{{ route('nodes.show', $node) }}" style="color:#f97316;text-decoration:none;">{{ $node->name }}</a></td>
                            <td class="muted">{{ $node->scheme }}://{{ $node->fqdn }}</td>
                            <td>{{ number_format($node->memory) }} MB</td>
                            <td>{{ number_format($node->disk) }} MB</td>
                            <td>{{ $node->servers_count }}</td>
                            <td>
                                @if ($node->maintenance_mode)
                                    <span class="status-badge status-maintenance">Maintenance</span>
                                @else
                                    <span class="status-badge status-active">Aktif</span>
                                @endif
                            </td>
                            <td class="actions">
                                <a href="{{ route('nodes.edit', $node) }}" class="btn btn-secondary">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
