@extends('layouts.app')

@section('title', 'Edit User - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>
    <a href="{{ route('users.index') }}">Users</a><span class="sep">&gt;</span>{{ $user->name }}
@endsection

@section('content')
    <h2>Edit User: {{ $user->name }}</h2>

    <div class="card">
        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('users.update', $user) }}">
            @csrf
            @method('PUT')

            <label for="name">Nama</label>
            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required>

            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required>

            <label for="password">Password Baru (kosongkan kalau nggak diganti)</label>
            <input type="password" name="password" id="password">

            <label>
                <input type="checkbox" name="root_admin" value="1" style="width:auto;display:inline-block;" {{ old('root_admin', $user->root_admin) ? 'checked' : '' }}>
                Admin
            </label>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Update User</button>
                <a href="{{ route('users.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top:0;color:#f87171;">Zona Bahaya</h3>
        <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Yakin hapus user ini?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Hapus User</button>
        </form>
    </div>
@endsection
