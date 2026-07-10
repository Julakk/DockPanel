@extends('layouts.app')

@section('title', 'Nests - DockPanel')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="margin:0;">Nests</h2>
        <a href="{{ route('nests.create') }}" class="btn btn-primary">+ Tambah Nest</a>
    </div>

    <div class="card">
        @if ($nests->isEmpty())
            <p class="muted">Belum ada nest. Contoh nest: "Minecraft", "SA-MP", "FiveM".</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Deskripsi</th>
                        <th>Jumlah Egg</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($nests as $nest)
                        <tr>
                            <td>{{ $nest->name }}</td>
                            <td class="muted">{{ $nest->description ?: '-' }}</td>
                            <td>{{ $nest->eggs_count }}</td>
                            <td class="actions">
                                <a href="{{ route('nests.edit', $nest) }}" class="btn btn-secondary">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <a href="{{ route('eggs.index') }}" class="muted" style="text-decoration:none;">→ Lihat semua Eggs</a>
@endsection
