@extends('layouts.app')

@section('title', 'API Credentials - DockPanel')

@section('breadcrumb')
    Account<span class="sep">&gt;</span>API Credentials
@endsection

@section('content')
    <div style="display:flex; gap:1rem; margin-bottom:1.5rem; border-bottom:1px solid #263349; overflow-x:auto;">
        <a href="{{ route('account.edit') }}" style="padding:0.6rem 0; color:#94a3b8; text-decoration:none; font-size:0.9rem; white-space:nowrap;">Account</a>
        <a href="{{ route('account.api-credentials.index') }}" style="padding:0.6rem 0; color:#f97316; text-decoration:none; border-bottom:2px solid #f97316; font-size:0.9rem; font-weight:600; white-space:nowrap;">API Credentials</a>
        <a href="{{ route('account.two-factor.show') }}" style="padding:0.6rem 0; color:#94a3b8; text-decoration:none; font-size:0.9rem; white-space:nowrap;">Two-Factor</a>
        <a href="{{ route('account.activity') }}" style="padding:0.6rem 0; color:#94a3b8; text-decoration:none; font-size:0.9rem; white-space:nowrap;">Activity</a>
    </div>

    <div class="card">
        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <h3 style="margin-top:0;">API Keys</h3>

        @if ($tokens->isEmpty())
            <div class="empty-state">
                <div class="icon">@include('partials.icon', ['name' => 'api', 'size' => 40])</div>
                <p>Belum ada API key buat akun kamu.</p>
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
                                <form method="POST" action="{{ route('account.api-credentials.destroy', $token->id) }}" onsubmit="return confirm('Cabut token ini?');">
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

        <form method="POST" action="{{ route('account.api-credentials.store') }}">
            @csrf
            <label for="name">Nama Token</label>
            <input type="text" name="name" id="name" placeholder="ex: Personal script" required>
            <button type="submit" class="btn btn-primary">+ Buat API Key</button>
        </form>
    </div>
@endsection
