@extends('layouts.app')

@section('title', 'Tambah Node - DockPanel')

@section('content')
    <h2>Tambah Node Baru</h2>

    <div class="card">
        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('nodes.store') }}">
            @csrf

            <label for="name">Nama Node</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" placeholder="ex: Node Jakarta 1" required>

            <label for="description">Deskripsi (opsional)</label>
            <textarea name="description" id="description" rows="2">{{ old('description') }}</textarea>

            <div class="row">
                <div>
                    <label for="fqdn">FQDN / IP</label>
                    <input type="text" name="fqdn" id="fqdn" value="{{ old('fqdn') }}" placeholder="node1.ahmadstore.id" required>
                </div>
                <div>
                    <label for="scheme">Scheme</label>
                    <select name="scheme" id="scheme">
                        <option value="https" {{ old('scheme') == 'https' ? 'selected' : '' }}>https</option>
                        <option value="http" {{ old('scheme') == 'http' ? 'selected' : '' }}>http</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div>
                    <label for="daemon_listen">Port Daemon Wings</label>
                    <input type="number" name="daemon_listen" id="daemon_listen" value="{{ old('daemon_listen', 8080) }}" required>
                </div>
                <div>
                    <label for="daemon_sftp">Port SFTP</label>
                    <input type="number" name="daemon_sftp" id="daemon_sftp" value="{{ old('daemon_sftp', 2022) }}" required>
                </div>
            </div>

            <div class="row">
                <div>
                    <label for="memory">Total Memory (MB)</label>
                    <input type="number" name="memory" id="memory" value="{{ old('memory') }}" placeholder="8192" required>
                </div>
                <div>
                    <label for="disk">Total Disk (MB)</label>
                    <input type="number" name="disk" id="disk" value="{{ old('disk') }}" placeholder="102400" required>
                </div>
            </div>

            <label>
                <input type="checkbox" name="public" value="1" style="width:auto;display:inline-block;" {{ old('public', true) ? 'checked' : '' }}>
                Node publik (bisa dipilih otomatis pas bikin server)
            </label>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Simpan Node</button>
                <a href="{{ route('nodes.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection
