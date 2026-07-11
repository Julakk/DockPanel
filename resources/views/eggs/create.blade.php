@extends('layouts.app')

@section('title', 'Tambah Egg - DockPanel')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Dashboard</a><span class="sep">/</span>
    <a href="{{ route('eggs.index') }}">Eggs</a><span class="sep">/</span>Tambah
@endsection

@section('content')
    <h2>Tambah Egg Baru</h2>

    <div class="card">
        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('eggs.store') }}">
            @csrf

            <label for="nest_id">Nest</label>
            <select name="nest_id" id="nest_id" required>
                <option value="">-- Pilih Nest --</option>
                @foreach ($nests as $nest)
                    <option value="{{ $nest->id }}" {{ old('nest_id') == $nest->id ? 'selected' : '' }}>{{ $nest->name }}</option>
                @endforeach
            </select>

            <label for="name">Nama Egg</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" placeholder="ex: Vanilla SA-MP Server" required>

            <label for="description">Deskripsi (opsional)</label>
            <textarea name="description" id="description" rows="2">{{ old('description') }}</textarea>

            <label for="docker_image">Docker Image</label>
            <input type="text" name="docker_image" id="docker_image" value="{{ old('docker_image') }}" placeholder="ghcr.io/pterodactyl/yolks:java_17" required>

            <label for="startup">Startup Command</label>
            <textarea name="startup" id="startup" rows="2" placeholder="./samp03svr" required>{{ old('startup') }}</textarea>
            <p class="muted" style="margin-top:-0.6rem;">Pakai {{ '{{VAR_NAME}}' }} buat placeholder variable.</p>

            <label for="script_container">Install Script Container</label>
            <input type="text" name="script_container" id="script_container" value="{{ old('script_container', 'alpine:3.4') }}">

            <label for="script_install">Install Script (opsional)</label>
            <textarea name="script_install" id="script_install" rows="4" placeholder="#!/bin/ash&#10;apk add curl">{{ old('script_install') }}</textarea>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Simpan Egg</button>
                <a href="{{ route('eggs.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection
