@extends('layouts.app')

@section('title', 'Edit Nest - DockPanel')

@section('content')
    <h2>Edit Nest: {{ $nest->name }}</h2>

    <div class="card">
        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('nests.update', $nest) }}">
            @csrf
            @method('PUT')

            <label for="name">Nama Nest</label>
            <input type="text" name="name" id="name" value="{{ old('name', $nest->name) }}" required>

            <label for="description">Deskripsi (opsional)</label>
            <textarea name="description" id="description" rows="2">{{ old('description', $nest->description) }}</textarea>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Update Nest</button>
                <a href="{{ route('nests.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top:0;color:#f87171;">Zona Bahaya</h3>
        <form method="POST" action="{{ route('nests.destroy', $nest) }}" onsubmit="return confirm('Yakin hapus nest ini?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Hapus Nest</button>
        </form>
    </div>
@endsection
