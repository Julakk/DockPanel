@extends('layouts.app')

@section('title', 'Tambah Location - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>
    <a href="{{ route('locations.index') }}">Locations</a><span class="sep">&gt;</span>Tambah
@endsection

@section('content')
    <h2>Tambah Location Baru</h2>

    <div class="card">
        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('locations.store') }}">
            @csrf

            <label for="short_code">Short Code</label>
            <input type="text" name="short_code" id="short_code" value="{{ old('short_code') }}" placeholder="ex: jakarta, sg, us-east" required>

            <label for="description">Deskripsi (opsional)</label>
            <textarea name="description" id="description" rows="2">{{ old('description') }}</textarea>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Simpan Location</button>
                <a href="{{ route('locations.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection
