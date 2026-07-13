@extends('layouts.app')

@section('title', 'Tambah Database Host - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>
    <a href="{{ route('databases.index') }}">Database Hosts</a><span class="sep">&gt;</span>Tambah
@endsection

@section('content')
    <h2>Tambah Database Host</h2>

    <div class="card">
        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('databases.store') }}">
            @csrf

            <label for="name">Nama</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" placeholder="ex: Database Utama" required>

            <div class="row">
                <div>
                    <label for="host">Host</label>
                    <input type="text" name="host" id="host" value="{{ old('host') }}" placeholder="127.0.0.1" required>
                </div>
                <div>
                    <label for="port">Port</label>
                    <input type="number" name="port" id="port" value="{{ old('port', 3306) }}" required>
                </div>
            </div>

            <label for="username">Username</label>
            <input type="text" name="username" id="username" value="{{ old('username') }}" required>

            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>

            <label for="node_id">Node (opsional)</label>
            <select name="node_id" id="node_id">
                <option value="">-- Nggak terikat node --</option>
                @foreach ($nodes as $node)
                    <option value="{{ $node->id }}" {{ old('node_id') == $node->id ? 'selected' : '' }}>{{ $node->name }}</option>
                @endforeach
            </select>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Simpan Host</button>
                <a href="{{ route('databases.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection
