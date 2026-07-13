@extends('layouts.app')

@section('title', 'Application API - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>Application API
@endsection

@section('content')
    <h2>Application API</h2>
    <p class="muted" style="margin-top:-0.6rem;">Control access credentials for managing this Panel via the API.</p>

    <div class="card">
        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <h3 style="margin-top:0;">Credentials List</h3>

        @if ($tokens->isEmpty())
            <div class="empty-state">
                <div class="icon">@include('partials.icon', ['name' => 'api', 'size' => 40])</div>
                <p>Belum ada token API.</p>
            </div>
        @else
            <table style="margin-bottom:1.5rem;">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Terakhir Dipakai</th>
                        <th>Dibuat</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tokens as $token)
                        <tr>
                            <td>{{ $token->name }}</td>
                            <td class="muted">{{ $token->last_used_at?->format('Y-m-d H:i') ?? 'Belum pernah' }}</td>
                            <td class="muted">{{ $token->created_at->format('Y-m-d H:i') }}</td>
                            <td class="actions">
                                <form method="POST" action="{{ route('api-keys.destroy', $token->id) }}" onsubmit="return confirm('Cabut token ini? Aplikasi yang pakai token ini bakal langsung ke-block.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">Cabut</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <form method="POST" action="{{ route('api-keys.store') }}">
            @csrf
            <div class="row">
                <div>
                    <label for="name">Nama Token</label>
                    <input type="text" name="name" id="name" placeholder="ex: Deployment script" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">+ Buat Token Baru</button>
        </form>
    </div>
@endsection
