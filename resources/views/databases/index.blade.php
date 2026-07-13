@extends('layouts.app')

@section('title', 'Database Hosts - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>Database Hosts
@endsection

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="margin:0;">Database Hosts</h2>
        <a href="{{ route('databases.create') }}" class="btn btn-primary">+ Tambah Host</a>
    </div>

    <div class="card">
        @if ($hosts->isEmpty())
            <div class="empty-state">
                <div class="icon">@include('partials.icon', ['name' => 'database', 'size' => 40])</div>
                <p>Belum ada database host. Tambah host MySQL/MariaDB buat dipakai server.</p>
                <a href="{{ route('databases.create') }}" class="btn btn-primary">+ Tambah Host</a>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Host</th>
                        <th>Port</th>
                        <th>Username</th>
                        <th>Node</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($hosts as $host)
                        <tr>
                            <td>{{ $host->name }}</td>
                            <td class="muted">{{ $host->host }}</td>
                            <td class="muted">{{ $host->port }}</td>
                            <td class="muted">{{ $host->username }}</td>
                            <td class="muted">{{ $host->node?->name ?? '-' }}</td>
                            <td class="actions">
                                <a href="{{ route('databases.edit', $host) }}" class="btn btn-secondary">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
