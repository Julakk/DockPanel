@extends('layouts.app')

@section('title', 'Users - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>Users
@endsection

@section('content')
    <h2 class="nd-title">Users <small>All registered users on the system.</small></h2>

    <div class="nd-box">
        <div class="nd-box-head">
            <h3>User List</h3>
            <div class="nd-tools">
                <div class="nd-search">
                    <input type="text" id="nd-search" placeholder="Search" autocomplete="off">
                    <span class="nd-search-btn"><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="10" cy="10" r="6"/><path d="M15 15l6 6"/></svg></span>
                </div>
                <a href="{{ route('users.create') }}" class="nd-btn nd-btn-blue">Create New</a>
            </div>
        </div>

        <div class="nd-box-body nd-flush">
            @if ($errors->any())
                <div class="error" style="margin:.9rem;">{{ $errors->first() }}</div>
            @endif

            @if ($users->isEmpty())
                <div class="empty-state">
                    <div class="icon">@include('partials.icon', ['name' => 'users', 'size' => 40])</div>
                    <p>Belum ada user selain kamu.</p>
                </div>
            @else
                <div class="nd-wrap">
                    <table class="nd-table" id="nd-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Email</th>
                                <th>Name</th>
                                <th class="c">2FA</th>
                                <th class="c">Servers Owned</th>
                                <th class="c">Can Access</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr data-name="{{ strtolower($user->email.' '.$user->name) }}">
                                    <td><span class="nd-code">{{ $user->id }}</span></td>
                                    <td>
                                        <a href="{{ route('users.edit', $user) }}">{{ $user->email }}</a>
                                        @if ($user->root_admin)<span class="nd-star" title="Admin">&#9733;</span>@endif
                                    </td>
                                    <td>{{ $user->name }}</td>
                                    <td class="c">
                                        @if ($user->hasTwoFactorEnabled())
                                            <span class="nd-on" title="2FA aktif"><svg viewBox="0 0 24 24" width="13" height="13" fill="currentColor"><path d="M6 10V8a6 6 0 0 1 12 0v2h1a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V11a1 1 0 0 1 1-1h1zm2 0h8V8a4 4 0 0 0-8 0v2z"/></svg></span>
                                        @else
                                            <span class="nd-off" title="2FA belum aktif"><svg viewBox="0 0 24 24" width="13" height="13" fill="currentColor"><path d="M17 10V8a5 5 0 0 0-9.6-2l1.9.7A3 3 0 0 1 15 8v2H5a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V11a1 1 0 0 0-1-1h-2z"/></svg></span>
                                        @endif
                                    </td>
                                    <td class="c"><a href="{{ route('servers.index', ['q' => $user->email]) }}">{{ $user->servers_count }}</a></td>
                                    <td class="c">{{ $user->subuser_of_servers_count }}</td>
                                    <td>
                                        <a href="{{ route('users.edit', $user) }}" class="nd-iconbtn round" title="Edit"><svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor"><path d="M3 17.25V21h3.75L18.8 8.95l-3.75-3.75L3 17.25zM20.7 7.05a1 1 0 0 0 0-1.4l-2.35-2.35a1 1 0 0 0-1.4 0l-1.85 1.85 3.75 3.75 1.85-1.85z"/></svg></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <script>
        (function () {
            var q = document.getElementById('nd-search');
            if (!q) return;
            q.addEventListener('input', function () {
                var v = q.value.toLowerCase();
                document.querySelectorAll('#nd-table tbody tr').forEach(function (r) {
                    r.style.display = (r.dataset.name || '').indexOf(v) > -1 ? '' : 'none';
                });
            });
        })();
    </script>
@endsection
