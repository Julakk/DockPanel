@extends('layouts.app')

@section('title', 'Edit Database Host - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>
    <a href="{{ route('databases.index') }}">Database Hosts</a><span class="sep">&gt;</span>Edit
@endsection

@section('content')
    <h2>Edit Database Host: {{ $host->name }}</h2>

    <div class="card">
        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('databases.update', $host) }}">
            @csrf
            @method('PUT')

            <label for="name">Nama</label>
            <input type="text" name="name" id="name" value="{{ old('name', $host->name) }}" required>

            <div class="row">
                <div>
                    <label for="host">Host</label>
                    <input type="text" name="host" id="host" value="{{ old('host', $host->host) }}" required>
                </div>
                <div>
                    <label for="port">Port</label>
                    <input type="number" name="port" id="port" value="{{ old('port', $host->port) }}" required>
                </div>
            </div>

            <label for="public_host">Alamat untuk Game Server (opsional)</label>
            <input type="text" name="public_host" id="public_host" value="{{ old('public_host', $host->public_host) }}" placeholder="172.17.0.1">
            <p class="muted" style="margin:.2rem 0 1rem;font-size:.85rem;">Alamat yang ditampilkan ke user buat konek dari dalam Docker. Kosong = sama dengan Host.</p>

            <label for="username">Username</label>
            <input type="text" name="username" id="username" value="{{ old('username', $host->username) }}" required>

            <label for="password">Password Baru (kosongkan kalau nggak diganti)</label>
            <input type="password" name="password" id="password">

            <label for="node_id">Node (opsional)</label>
            <select name="node_id" id="node_id">
                <option value="">-- Nggak terikat node --</option>
                @foreach ($nodes as $node)
                    <option value="{{ $node->id }}" {{ old('node_id', $host->node_id) == $node->id ? 'selected' : '' }}>{{ $node->name }}</option>
                @endforeach
            </select>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Update Host</button>
                <a href="{{ route('databases.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Koneksi</h3>
        <form method="POST" action="{{ route('databases.test', $host) }}" style="display:inline;">
            @csrf
            <button type="submit" class="btn btn-secondary">Tes Koneksi</button>
        </form>
        @if ($pmaEnabled)
            <form method="POST" action="{{ route('databases.phpmyadmin', $host) }}" target="_blank" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn-secondary">Buka phpMyAdmin</button>
            </form>
        @endif
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Databases di host ini ({{ $host->databases->count() }})</h3>
        @if ($host->databases->isEmpty())
            <p class="muted" style="margin:0;">Belum ada database.</p>
        @else
            <table>
                <thead>
                    <tr><th>Database</th><th>Server</th><th>Username</th><th>Connections From</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($host->databases as $db)
                        <tr>
                            <td>{{ $db->database }}</td>
                            <td>
                                @if ($db->server)
                                    <a href="{{ route('servers.show', $db->server) }}">{{ $db->server->name }}</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="muted">{{ $db->username }}</td>
                            <td class="muted">{{ $db->remote ?: '%' }}</td>
                            <td>
                                <form method="POST" action="{{ route('servers.databases.destroy', [$db->server_id, $db]) }}" onsubmit="return confirm('Hapus database ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="card">
        <h3 style="margin-top:0;color:#f87171;">Zona Bahaya</h3>
        <form method="POST" action="{{ route('databases.destroy', $host) }}" onsubmit="return confirm('Yakin hapus database host ini?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Hapus Host</button>
        </form>
    </div>
@endsection
