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
        <h3 style="margin-top:0;color:#f87171;">Zona Bahaya</h3>
        <form method="POST" action="{{ route('databases.destroy', $host) }}" onsubmit="return confirm('Yakin hapus database host ini?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Hapus Host</button>
        </form>
    </div>
@endsection
