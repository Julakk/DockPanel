@extends('layouts.app')

@section('title', 'Users - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>Users
@endsection

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="margin:0;">Users</h2>
        <a href="{{ route('users.create') }}" class="btn btn-primary">+ Tambah User</a>
    </div>

    <div class="card">
        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        @if ($users->isEmpty())
            <div class="empty-state">
                <div class="icon">@include('partials.icon', ['name' => 'users', 'size' => 40])</div>
                <p>Belum ada user selain kamu.</p>
                <a href="{{ route('users.create') }}" class="btn btn-primary">+ Tambah User</a>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Server</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td class="muted">{{ $user->email }}</td>
                            <td>
                                @if ($user->root_admin)
                                    <span class="status-badge status-active">Admin</span>
                                @else
                                    <span class="status-badge status-maintenance">User</span>
                                @endif
                            </td>
                            <td>{{ $user->servers_count }}</td>
                            <td class="actions">
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-secondary">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
