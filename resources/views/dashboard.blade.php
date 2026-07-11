@extends('layouts.app')

@section('title', 'Dashboard - DockPanel')

@section('content')
    <h2>Halo, {{ $user->name }} @include('partials.icon', ['name' => 'sparkle', 'size' => 22])</h2>
    <p class="muted">Selamat datang di DockPanel.</p>

    <div class="card">
        <h3 style="margin-top:0;">Quick Links</h3>
        <div style="display:flex; gap:0.6rem; flex-wrap:wrap;">
            <a href="{{ route('nodes.index') }}" class="btn btn-primary">@include('partials.icon', ['name' => 'server', 'size' => 16]) Kelola Nodes</a>
            <a href="{{ route('nests.index') }}" class="btn btn-secondary">@include('partials.icon', ['name' => 'globe', 'size' => 16]) Kelola Nests</a>
            <a href="{{ route('eggs.index') }}" class="btn btn-secondary">@include('partials.icon', ['name' => 'egg', 'size' => 16]) Kelola Eggs</a>
            <a href="{{ route('servers.index') }}" class="btn btn-secondary">@include('partials.icon', ['name' => 'package', 'size' => 16]) Kelola Servers</a>
        </div>
    </div>
@endsection
