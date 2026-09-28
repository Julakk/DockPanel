@extends('layouts.app')

@section('title', 'Audit Log')

@section('breadcrumb')
<h1>Audit Log</h1>
<p class="muted">Semua aktivitas yang tercatat di panel (terbaru dulu).</p>
<div style="overflow-x:auto">
<table style="width:100%;border-collapse:collapse;font-size:.85rem">
    <thead>
        <tr><th align="left">#</th><th align="left">Waktu</th><th align="left">Detail</th></tr>
    </thead>
    <tbody>
    @forelse ($logs as $log)
        @php
            $attrs = collect($log->getAttributes())->except(['id', 'created_at', 'updated_at'])->filter(fn ($v) => $v !== null && $v !== '');
        @endphp
        <tr style="border-top:1px solid rgba(127,127,127,.25);vertical-align:top">
            <td style="padding:.4rem .5rem .4rem 0">{{ $log->id }}</td>
            <td style="padding:.4rem .5rem .4rem 0;white-space:nowrap">{{ $log->created_at?->format('d M Y H:i:s') }}</td>
            <td style="padding:.4rem 0;word-break:break-all">
                @foreach ($attrs as $k => $v)
                    <div><span class="muted">{{ $k }}:</span> {{ is_string($v) ? $v : json_encode($v) }}</div>
                @endforeach
            </td>
        </tr>
    @empty
        <tr><td colspan="3">Belum ada aktivitas.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:1rem;display:flex;gap:1rem">
    @if ($logs->previousPageUrl())<a href="{{ $logs->previousPageUrl() }}">&larr; Sebelumnya</a>@endif
    @if ($logs->nextPageUrl())<a href="{{ $logs->nextPageUrl() }}">Berikutnya &rarr;</a>@endif
</div>
@endsection
