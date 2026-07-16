@extends('layouts.app')

@section('title', 'Activity - DockPanel')

@section('breadcrumb')
    Account<span class="sep">&gt;</span>Activity
@endsection

@section('content')
    <div style="display:flex; gap:1rem; margin-bottom:1.5rem; border-bottom:1px solid #263349; overflow-x:auto;">
        <a href="{{ route('account.edit') }}" style="padding:0.6rem 0; color:#94a3b8; text-decoration:none; font-size:0.9rem; white-space:nowrap;">Account</a>
        <a href="{{ route('account.api-credentials.index') }}" style="padding:0.6rem 0; color:#94a3b8; text-decoration:none; font-size:0.9rem; white-space:nowrap;">API Credentials</a>
        <a href="{{ route('account.two-factor.show') }}" style="padding:0.6rem 0; color:#94a3b8; text-decoration:none; font-size:0.9rem; white-space:nowrap;">Two-Factor</a>
        <a href="{{ route('account.activity') }}" style="padding:0.6rem 0; color:#f97316; text-decoration:none; border-bottom:2px solid #f97316; font-size:0.9rem; font-weight:600; white-space:nowrap;">Activity</a>
    </div>

    <div class="card">
        @if ($logs->isEmpty())
            <div class="empty-state">
                <p>Belum ada aktivitas tercatat.</p>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Server</th>
                        <th>IP</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td>{{ $log->event }}</td>
                            <td class="muted">{{ $log->server?->name ?? '-' }}</td>
                            <td class="muted">{{ $log->ip }}</td>
                            <td class="muted">{{ $log->created_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="margin-top:1rem;">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
@endsection
