@extends('layouts.app')

@section('title', 'Eggs - DockPanel')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Dashboard</a><span class="sep">/</span>Eggs
@endsection

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="margin:0;">Eggs</h2>
        <div>
            <a href="{{ route('eggs.import.form') }}" class="btn btn-secondary">Import JSON</a>
            <a href="{{ route('eggs.create') }}" class="btn btn-primary">+ Tambah Egg</a>
        </div>
    </div>

    <div class="card">
        @if ($eggs->isEmpty())
            <div class="empty-state">
                <div class="icon">@include('partials.icon', ['name' => 'egg', 'size' => 40])</div>
                <p>Belum ada egg. Bikin egg baru atau import dari format JSON Pterodactyl.</p>
                <a href="{{ route('eggs.create') }}" class="btn btn-primary">+ Tambah Egg</a>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Nest</th>
                        <th>Docker Image</th>
                        <th>Dipakai</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($eggs as $egg)
                        <tr>
                            <td>{{ $egg->name }}</td>
                            <td class="muted">{{ $egg->nest->name }}</td>
                            <td class="muted">{{ $egg->docker_image }}</td>
                            <td>{{ $egg->servers_count }}</td>
                            <td class="actions">
                                <a href="{{ route('eggs.edit', $egg) }}" class="btn btn-secondary">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <a href="{{ route('nests.index') }}" class="muted" style="text-decoration:none;">← Lihat semua Nests</a>
@endsection
