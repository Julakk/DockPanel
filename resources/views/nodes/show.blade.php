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

    <div class="card">
        <h3 style="margin-top:0;">Allocations (IP:Port)</h3>

        @if ($node->allocations->isEmpty())
            <p class="muted">Belum ada allocation. Tambah dulu biar bisa dipakai bikin server.</p>
        @else
            <table style="margin-bottom:1.5rem;">
                <thead>
                    <tr>
                        <th>IP</th>
                        <th>Port</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($node->allocations as $alloc)
                        <tr>
                            <td>{{ $alloc->ip }}</td>
                            <td>{{ $alloc->port }}</td>
                            <td>
                                @if ($alloc->isAssigned())
                                    <span style="color:#fbbf24;">Dipakai (server #{{ $alloc->server_id }})</span>
                                @else
                                    <span style="color:#4ade80;">Available</span>
                                @endif
                            </td>
                            <td class="actions">
                                @unless ($alloc->isAssigned())
                                    <form method="POST" action="{{ route('nodes.allocations.destroy', [$node, $alloc]) }}" onsubmit="return confirm('Hapus allocation ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">Hapus</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <form method="POST" action="{{ route('nodes.allocations.store', $node) }}">
            @csrf
            <div class="row">
                <div>
                    <label for="ip">IP</label>
                    <input type="text" name="ip" id="ip" placeholder="{{ $node->fqdn }}" required>
                </div>
                <div>
                    <label for="port_start">Port Awal</label>
                    <input type="number" name="port_start" id="port_start" placeholder="7777" required>
                </div>
                <div>
                    <label for="port_end">Port Akhir (opsional, buat range)</label>
                    <input type="number" name="port_end" id="port_end" placeholder="7787">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">+ Tambah Allocation</button>
        </form>
    </div>

    <a href="{{ route('nodes.index') }}" class="muted" style="text-decoration:none;">← Kembali ke daftar node</a>
@endsection
