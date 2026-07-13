@extends('layouts.app')

@section('title', 'Edit Mount - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>
    <a href="{{ route('mounts.index') }}">Mounts</a><span class="sep">&gt;</span>Edit
@endsection

@section('content')
    <h2>Edit Mount: {{ $mount->name }}</h2>

    <div class="card">
        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('mounts.update', $mount) }}">
            @csrf
            @method('PUT')

            <label for="name">Nama</label>
            <input type="text" name="name" id="name" value="{{ old('name', $mount->name) }}" required>

            <label for="description">Deskripsi (opsional)</label>
            <textarea name="description" id="description" rows="2">{{ old('description', $mount->description) }}</textarea>

            <div class="row">
                <div>
                    <label for="source">Source Path (di host)</label>
                    <input type="text" name="source" id="source" value="{{ old('source', $mount->source) }}" required>
                </div>
                <div>
                    <label for="target">Target Path (di container)</label>
                    <input type="text" name="target" id="target" value="{{ old('target', $mount->target) }}" required>
                </div>
            </div>

            <label>
                <input type="checkbox" name="read_only" value="1" style="width:auto;display:inline-block;" {{ old('read_only', $mount->read_only) ? 'checked' : '' }}>
                Read-only
            </label>

            @php $currentNodeIds = old('node_ids', $mount->nodes->pluck('id')->toArray()); @endphp
            <label for="node_ids" style="margin-top:0.8rem;">Aktifkan di Node</label>
            <select name="node_ids[]" id="node_ids" multiple size="4">
                @foreach ($nodes as $node)
                    <option value="{{ $node->id }}" {{ in_array($node->id, $currentNodeIds) ? 'selected' : '' }}>{{ $node->name }}</option>
                @endforeach
            </select>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Update Mount</button>
                <a href="{{ route('mounts.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top:0;color:#f87171;">Zona Bahaya</h3>
        <form method="POST" action="{{ route('mounts.destroy', $mount) }}" onsubmit="return confirm('Yakin hapus mount ini?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Hapus Mount</button>
        </form>
    </div>
@endsection
