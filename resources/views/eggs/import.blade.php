@extends('layouts.app')

@section('title', 'Import Egg - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>
    <a href="{{ route('eggs.index') }}">Eggs</a><span class="sep">&gt;</span>Import
@endsection

@section('content')
    <h2>Import Egg dari JSON</h2>
    <p class="muted">Format JSON kompatibel sama file export egg Pterodactyl.</p>

    <div class="card">
        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('eggs.import') }}" enctype="multipart/form-data">
            @csrf

            <label for="nest_id">Masukin ke Nest</label>
            <select name="nest_id" id="nest_id" required>
                <option value="">-- Pilih Nest --</option>
                @foreach ($nests as $nest)
                    <option value="{{ $nest->id }}">{{ $nest->name }}</option>
                @endforeach
            </select>

            <label for="egg_json">File JSON Egg</label>
            <input type="file" name="egg_json" id="egg_json" accept=".json" required>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Import</button>
                <a href="{{ route('eggs.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection
