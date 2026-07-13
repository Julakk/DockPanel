@extends('layouts.app')

@section('title', 'Tambah User - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>
    <a href="{{ route('users.index') }}">Users</a><span class="sep">&gt;</span>Tambah
@endsection

@section('content')
    <h2>Tambah User Baru</h2>

    <div class="card">
        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('users.store') }}">
            @csrf

            <label for="name">Nama</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required>

            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required>

            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>

            <label>
                <input type="checkbox" name="root_admin" value="1" style="width:auto;display:inline-block;" {{ old('root_admin') ? 'checked' : '' }}>
                Jadikan admin
            </label>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Simpan User</button>
                <a href="{{ route('users.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection
