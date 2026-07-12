@extends('layouts.app')

@section('title', 'Tambah Nest - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>
    <a href="{{ route('nests.index') }}">Nests</a><span class="sep">&gt;</span>Tambah
@endsection

@section('content')
    <h2>Tambah Nest Baru</h2>

    <div class="card">
        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('nests.store') }}">
            @csrf

            <label for="name">Nama Nest</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" placeholder="ex: Minecraft, SA-MP, FiveM" required>

            <label for="description">Deskripsi (opsional)</label>
            <textarea name="description" id="description" rows="2">{{ old('description') }}</textarea>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Simpan Nest</button>
                <a href="{{ route('nests.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection
