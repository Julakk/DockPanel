@extends('layouts.app')

@section('title', 'Tambah Mount - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>
    <a href="{{ route('mounts.index') }}">Mounts</a><span class="sep">&gt;</span>Tambah
@endsection

@section('content')
    <h2>Tambah Mount Baru</h2>

    <div class="card">
        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('mounts.store') }}">
            @csrf

            <label for="name">Nama</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required>

            <label for="description">Deskripsi (opsional)</label>
            <textarea name="description" id="description" rows="2">{{ old('description') }}</textarea>

            <div class="row">
                <div>
                    <label for="source">Source Path (di host)</label>
                    <input type="text" name="source" id="source" value="{{ old('source') }}" placeholder="/var/lib/shared-maps" required>
                </div>
                <div>
                    <label for="target">Target Path (di container)</label>
                    <input type="text" name="target" id="target" value="{{ old('target') }}" placeholder="/mnt/maps" required>
                </div>
            </div>

            <label>
                <input type="checkbox" name="read_only" value="1" style="width:auto;display:inline-block;" {{ old('read_only') ? 'checked' : '' }}>
                Read-only
            </label>

            <label for="node_ids" style="margin-top:0.8rem;">Aktifkan di Node</label>
            <select name="node_ids[]" id="node_ids" multiple size="4">
                @foreach ($nodes as $node)
                    <option value="{{ $node->id }}" {{ collect(old('node_ids'))->contains($node->id) ? 'selected' : '' }}>{{ $node->name }}</option>
                @endforeach
            </select>
            <p class="muted" style="margin-top:-0.6rem;">Tahan Ctrl (atau tap multi) buat pilih lebih dari satu.</p>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Simpan Mount</button>
                <a href="{{ route('mounts.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection
