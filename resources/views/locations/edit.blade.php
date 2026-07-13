@extends('layouts.app')

@section('title', 'Edit Location - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>
    <a href="{{ route('locations.index') }}">Locations</a><span class="sep">&gt;</span>Edit
@endsection

@section('content')
    <h2>Edit Location: {{ $location->short_code }}</h2>

    <div class="card">
        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('locations.update', $location) }}">
            @csrf
            @method('PUT')

            <label for="short_code">Short Code</label>
            <input type="text" name="short_code" id="short_code" value="{{ old('short_code', $location->short_code) }}" required>

            <label for="description">Deskripsi (opsional)</label>
            <textarea name="description" id="description" rows="2">{{ old('description', $location->description) }}</textarea>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Update Location</button>
                <a href="{{ route('locations.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top:0;color:#f87171;">Zona Bahaya</h3>
        <form method="POST" action="{{ route('locations.destroy', $location) }}" onsubmit="return confirm('Yakin hapus location ini?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Hapus Location</button>
        </form>
    </div>
@endsection
