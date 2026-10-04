@extends('layouts.app')

@section('title', 'Database Hosts - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>Database Hosts
@endsection

@section('content')
    <h2 class="nd-title">Database Hosts <small>Host MySQL/MariaDB tempat database server dibuat.</small></h2>

    <div class="nd-box">
        <div class="nd-box-head">
            <h3>Host List</h3>
            <div class="nd-tools">
                <a href="{{ route('databases.create') }}" class="nd-btn nd-btn-blue">Create New</a>
            </div>
        </div>

        <div class="nd-box-body nd-flush">
            @if ($hosts->isEmpty())
                <div class="empty-state">
                    <div class="icon">@include('partials.icon', ['name' => 'database', 'size' => 40])</div>
                    <p>Belum ada database host. Tambah host MySQL/MariaDB buat dipakai server.</p>
                </div>
            @else
                <div class="nd-wrap">
                    <table class="nd-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Host</th>
                                <th>Endpoint Game Server</th>
                                <th>Username</th>
                                <th>Linked Node</th>
                                <th class="c">Databases</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($hosts as $host)
                                <tr>
                                    <td><code>{{ $host->id }}</code></td>
                                    <td><a href="{{ route('databases.edit', $host) }}">{{ $host->name }}</a></td>
                                    <td><code>{{ $host->host }}:{{ $host->port }}</code></td>
                                    <td><code>{{ $host->endpoint() }}:{{ $host->port }}</code></td>
                                    <td class="nd-mute">{{ $host->username }}</td>
                                    <td class="nd-mute">{{ $host->node?->name ?? '-' }}</td>
                                    <td class="c">{{ $host->databases_count }}</td>
                                    <td><a href="{{ route('databases.edit', $host) }}" class="nd-btn nd-btn-blue">Manage</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
