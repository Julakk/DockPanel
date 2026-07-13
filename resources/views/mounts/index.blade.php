@extends('layouts.app')

@section('title', 'Mounts - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>Mounts
@endsection

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="margin:0;">Mounts</h2>
        <a href="{{ route('mounts.create') }}" class="btn btn-primary">+ Tambah Mount</a>
    </div>

    <div class="card">
        @if ($mounts->isEmpty())
            <div class="empty-state">
                <div class="icon">@include('partials.icon', ['name' => 'mounts', 'size' => 40])</div>
                <p>Belum ada mount point.</p>
                <a href="{{ route('mounts.create') }}" class="btn btn-primary">+ Tambah Mount</a>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Source</th>
                        <th>Target</th>
                        <th>Nodes</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($mounts as $mount)
                        <tr>
                            <td>{{ $mount->name }}</td>
                            <td class="muted">{{ $mount->source }}</td>
                            <td class="muted">{{ $mount->target }}</td>
                            <td>{{ $mount->nodes_count }}</td>
                            <td class="actions">
                                <a href="{{ route('mounts.edit', $mount) }}" class="btn btn-secondary">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
