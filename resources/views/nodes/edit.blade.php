@extends('layouts.app')

@section('title', 'Edit Node - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>
    <a href="{{ route('nodes.index') }}">Nodes</a><span class="sep">&gt;</span>Edit
@endsection

@section('content')
    <h2>Edit Node: {{ $node->name }}</h2>

    <div class="card">
        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('nodes.update', $node) }}">
            @csrf
            @method('PUT')

            <label for="name">Nama Node</label>
            <input type="text" name="name" id="name" value="{{ old('name', $node->name) }}" required>

            <label for="description">Deskripsi (opsional)</label>
            <textarea name="description" id="description" rows="2">{{ old('description', $node->description) }}</textarea>

            <div class="row">
                <div>
                    <label for="fqdn">FQDN / IP</label>
                    <input type="text" name="fqdn" id="fqdn" value="{{ old('fqdn', $node->fqdn) }}" required>
                </div>
                <div>
                    <label for="scheme">Scheme</label>
                    <select name="scheme" id="scheme">
                        <option value="https" {{ old('scheme', $node->scheme) == 'https' ? 'selected' : '' }}>https</option>
                        <option value="http" {{ old('scheme', $node->scheme) == 'http' ? 'selected' : '' }}>http</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div>
                    <label for="daemon_listen">Port Daemon Wings</label>
                    <input type="number" name="daemon_listen" id="daemon_listen" value="{{ old('daemon_listen', $node->daemon_listen) }}" required>
                </div>
                <div>
                    <label for="daemon_sftp">Port SFTP</label>
                    <input type="number" name="daemon_sftp" id="daemon_sftp" value="{{ old('daemon_sftp', $node->daemon_sftp) }}" required>
                </div>
            </div>

            <div class="row">
                <div>
                    <label for="memory">Total Memory (MB)</label>
                    <input type="number" name="memory" id="memory" value="{{ old('memory', $node->memory) }}" required>
                </div>
                <div>
                    <label for="disk">Total Disk (MB)</label>
                    <input type="number" name="disk" id="disk" value="{{ old('disk', $node->disk) }}" required>
                </div>
            </div>

            <label>
                <input type="checkbox" name="public" value="1" style="width:auto;display:inline-block;" {{ old('public', $node->public) ? 'checked' : '' }}>
                Node publik
            </label>

            <label style="margin-top:0.8rem;">
                <input type="checkbox" name="behind_proxy" value="1" style="width:auto;display:inline-block;" {{ old('behind_proxy', $node->behind_proxy) ? 'checked' : '' }}>
                Node di belakang proxy (Cloudflare, dll)
            </label>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Update Node</button>
                <a href="{{ route('nodes.show', $node) }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top:0;color:#f87171;">Zona Bahaya</h3>
        <form method="POST" action="{{ route('nodes.destroy', $node) }}" onsubmit="return confirm('Yakin hapus node ini? Nggak bisa dibalikin.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Hapus Node</button>
        </form>
    </div>
@endsection
