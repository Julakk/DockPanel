@extends('layouts.app')

@section('title', 'My Servers - DockPanel')

@section('content')
    <h2 style="margin-top:0;">Halo, {{ $user->name }} @include('partials.icon', ['name' => 'sparkle', 'size' => 22])</h2>
    <p class="muted" style="margin-top:-0.6rem;">Ini daftar server yang kamu punya akses.</p>

    @if ($servers->isEmpty())
        <div class="card">
            <div class="empty-state">
                <div class="icon">@include('partials.icon', ['name' => 'package', 'size' => 40])</div>
                <p>Kamu belum punya server. Hubungi admin buat dibuatin server baru.</p>
            </div>
        </div>
    @else
        @foreach ($servers as $server)
            <div class="server-card status-{{ $server->status }}-border">
                <div class="server-card-icon">
                    @include('partials.icon', ['name' => 'package', 'size' => 18])
                </div>

                <div>
                    <div class="server-card-name">{{ $server->name }}</div>
                    <div class="server-card-sub">{{ $server->node->name }} / {{ $server->egg->name }}</div>
                </div>

                <span class="status-badge status-{{ $server->status }}" style="margin-left:0.5rem;">{{ $server->status }}</span>

                {{-- Resource usage: placeholder sampai Wings aktif dan bisa lapor data beneran --}}
                <div class="server-card-stats">
                    <div class="stat">
                        <div class="stat-label">CPU</div>
                        <div class="stat-bar"><div class="stat-bar-fill" style="width:0%;"></div></div>
                        <div class="stat-value">—</div>
                    </div>
                    <div class="stat">
                        <div class="stat-label">Memory</div>
                        <div class="stat-bar"><div class="stat-bar-fill" style="width:0%;"></div></div>
                        <div class="stat-value">—</div>
                    </div>
                    <div class="stat">
                        <div class="stat-label">Disk</div>
                        <div class="stat-bar"><div class="stat-bar-fill" style="width:0%;"></div></div>
                        <div class="stat-value">—</div>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
@endsection
