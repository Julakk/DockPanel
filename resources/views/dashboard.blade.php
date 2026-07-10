@extends('layouts.app')

@section('title', 'Dashboard - DockPanel')

@section('content')
    <h2>Halo, {{ $user->name }} 👋</h2>
    <p class="muted">Selamat datang di DockPanel.</p>

    <div class="card">
        <h3 style="margin-top:0;">Quick Links</h3>
        <a href="{{ route('nodes.index') }}" class="btn btn-primary">Kelola Nodes</a>
    </div>
@endsection
