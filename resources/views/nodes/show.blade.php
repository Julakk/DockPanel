@extends('layouts.app')

@section('title', $node->name . ' - DockPanel')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="margin:0;">{{ $node->name }}</h2>
        <a href="{{ route('nodes.edit', $node) }}" class="btn btn-secondary">Edit</a>
    </div>

    <div class="card">
        <table>
            <tr><th>FQDN</th><td>{{ $node->scheme }}://{{ $node->fqdn }}:{{ $node->daemon_listen }}</td></tr>
            <tr><th>Port SFTP</th><td>{{ $node->daemon_sftp }}</td></tr>
            <tr><th>Memory</th><td>{{ $node->memoryUsed() }} / {{ number_format($node->memory) }} MB</td></tr>
            <tr><th>Disk</th><td>{{ $node->diskUsed() }} / {{ number_format($node->disk) }} MB</td></tr>
            <tr><th>Server Terpasang</th><td>{{ $node->servers_count }}</td></tr>
            <tr><th>Publik</th><td>{{ $node->public ? 'Ya' : 'Tidak' }}</td></tr>
            <tr><th>Status</th><td>{{ $node->maintenance_mode ? 'Maintenance' : 'Aktif' }}</td></tr>
        </table>
    </div>

    @if ($node->description)
        <div class="card">
            <p class="muted" style="margin:0;">{{ $node->description }}</p>
        </div>
    @endif

    <a href="{{ route('nodes.index') }}" class="muted" style="text-decoration:none;">← Kembali ke daftar node</a>
@endsection
