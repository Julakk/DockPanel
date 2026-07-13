@extends('layouts.app')

@section('title', 'Locations - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>Locations
@endsection

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="margin:0;">Locations</h2>
        <a href="{{ route('locations.create') }}" class="btn btn-primary">+ Tambah Location</a>
    </div>

    <div class="card">
        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        @if ($locations->isEmpty())
            <div class="empty-state">
                <div class="icon">@include('partials.icon', ['name' => 'location', 'size' => 40])</div>
                <p>Belum ada location. Bikin buat kategorisasi node berdasarkan lokasi fisik.</p>
                <a href="{{ route('locations.create') }}" class="btn btn-primary">+ Tambah Location</a>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Short Code</th>
                        <th>Deskripsi</th>
                        <th>Nodes</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($locations as $location)
                        <tr>
                            <td>{{ $location->short_code }}</td>
                            <td class="muted">{{ $location->description ?: '-' }}</td>
                            <td>{{ $location->nodes_count }}</td>
                            <td class="actions">
                                <a href="{{ route('locations.edit', $location) }}" class="btn btn-secondary">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
