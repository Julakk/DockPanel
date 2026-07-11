@extends('layouts.app')

@section('title', 'Overview')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>Overview
@endsection

@section('content')
    <h2 style="margin-top:0;">Administrative Overview</h2>
    <p class="muted" style="margin-top:-0.6rem;">A quick glance at your system, {{ $user->name }}.</p>

    <div class="card">
        <h3 style="margin-top:0;">System Information</h3>
        <p class="muted" style="margin:0;">
            You are running <strong style="color:#e2e8f0;">DockPanel</strong> — self-hosted game server management panel.
            Wings daemon belum aktif, jadi resource usage server masih placeholder.
        </p>
    </div>

    <div class="row">
        <div class="card" style="flex:1; min-width:150px; text-align:center;">
            <div class="muted" style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em;">Nodes</div>
            <div style="font-size:1.8rem; font-weight:700; margin-top:0.3rem;">{{ $nodeCount }}</div>
        </div>
        <div class="card" style="flex:1; min-width:150px; text-align:center;">
            <div class="muted" style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em;">Servers</div>
            <div style="font-size:1.8rem; font-weight:700; margin-top:0.3rem;">{{ $serverCount }}</div>
        </div>
        <div class="card" style="flex:1; min-width:150px; text-align:center;">
            <div class="muted" style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em;">Eggs</div>
            <div style="font-size:1.8rem; font-weight:700; margin-top:0.3rem;">{{ $eggCount }}</div>
        </div>
    </div>

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
