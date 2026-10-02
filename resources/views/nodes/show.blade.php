@extends('layouts.app')

@section('title', $node->name . ' - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>
    <a href="{{ route('nodes.index') }}">Nodes</a><span class="sep">&gt;</span>{{ $node->name }}
@endsection

@section('content')
    @php
        $memUsed = $node->memoryUsed();
        $diskUsed = $node->diskUsed();
        $memPct = $node->memory > 0 ? round($memUsed / $node->memory * 100) : 0;
        $diskPct = $node->disk > 0 ? round($diskUsed / $node->disk * 100) : 0;
        $tone = fn ($p) => $p >= 90 ? 'nd-red' : ($p >= 50 ? 'nd-orange' : 'nd-green');
    @endphp

    <h2 class="nd-title">{{ $node->name }} <small>A quick overview of your node.</small></h2>

    @include('nodes._tabs', ['active' => 'about'])

    <div class="nd-grid">
        <div>
            <div class="nd-box">
                <div class="nd-box-head"><h3>Information</h3></div>
                <div class="nd-box-body nd-flush">
                    <table class="nd-table nd-kv">
                        <tr>
                            <td>Daemon Version</td>
                            <td>
                                @if ($wings['ok'])
                                    <span class="nd-code">v{{ $wings['data']['version'] ?? '?' }}</span>
                                @else
                                    <span class="nd-bad">Offline</span>
                                    <div class="nd-mute" style="margin-top:.3rem;">{{ $wings['error'] }}</div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>System Information</td>
                            <td>
                                @if ($wings['ok'])
                                    <span class="nd-code">{{ ucfirst($wings['data']['os'] ?? '?') }} ({{ $wings['data']['architecture'] ?? '?' }})</span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Total CPU Threads</td>
                            <td>{{ $wings['ok'] ? ($wings['data']['cpu_count'] ?? '?') : '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="nd-box nd-danger">
                <div class="nd-box-head"><h3>Delete Node</h3></div>
                <div class="nd-box-body">
                    <p style="margin:0 0 .9rem;">Deleting a node is a irreversible action and will immediately remove this node from the panel. There must be no servers associated with this node in order to continue.</p>
                    @error('delete')
                        <p class="nd-bad" style="margin:0 0 .9rem;">{{ $message }}</p>
                    @enderror
                    <form method="POST" action="{{ route('nodes.destroy', $node) }}" onsubmit="return confirm('Hapus node {{ $node->name }}?');" style="text-align:right;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="nd-btn nd-btn-red">Yes, Delete This Node</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="nd-box">
            <div class="nd-box-head"><h3>At-a-Glance</h3></div>
            <div class="nd-box-body">
                <div class="nd-tile {{ $tone($diskPct) }}">
                    <div class="ico"><svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></div>
                    <div class="txt">
                        <div class="lbl">Disk Space Allocated</div>
                        <div class="num">{{ number_format($diskUsed) }} / {{ number_format($node->disk) }} MiB</div>
                        <div class="bar"><i style="width:{{ min(100, $diskPct) }}%"></i></div>
                    </div>
                </div>
                <div class="nd-tile {{ $tone($memPct) }}">
                    <div class="ico"><svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="18" height="12" rx="1"/><path d="M7 10v4M10 10v4M13 10v4M16 10v4"/></svg></div>
                    <div class="txt">
                        <div class="lbl">Memory Allocated</div>
                        <div class="num">{{ number_format($memUsed) }} / {{ number_format($node->memory) }} MiB</div>
                        <div class="bar"><i style="width:{{ min(100, $memPct) }}%"></i></div>
                    </div>
                </div>
                <div class="nd-tile nd-blue">
                    <div class="ico"><svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3l9 5-9 5-9-5z"/><path d="M3 12l9 5 9-5M3 16l9 5 9-5"/></svg></div>
                    <div class="txt">
                        <div class="lbl">Total Servers</div>
                        <div class="num">{{ $node->servers_count }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($node->description)
        <div class="card">
            <p class="muted" style="margin:0;">{{ $node->description }}</p>
        </div>
    @endif

    <div class="card" id="allocation">
        <h3 style="margin-top:0;">Allocations (IP:Port)</h3>

        @if ($node->allocations->isEmpty())
            <div class="empty-state">
                <div class="icon">@include('partials.icon', ['name' => 'plug', 'size' => 40])</div>
                <p>Belum ada allocation. Tambah dulu biar bisa dipakai bikin server.</p>
            </div>
        @else
            <table style="margin-bottom:1.5rem;">
                <thead>
                    <tr>
                        <th>IP</th>
                        <th>Port</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($node->allocations as $alloc)
                        <tr>
                            <td>{{ $alloc->ip }}</td>
                            <td>{{ $alloc->port }}</td>
                            <td>
                                @if ($alloc->isAssigned())
                                    <span class="status-badge status-installing">Dipakai (#{{ $alloc->server_id }})</span>
                                @else
                                    <span class="status-badge status-active">Available</span>
                                @endif
                            </td>
                            <td class="actions">
                                @unless ($alloc->isAssigned())
                                    <form method="POST" action="{{ route('nodes.allocations.destroy', [$node, $alloc]) }}" onsubmit="return confirm('Hapus allocation ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">Hapus</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <form method="POST" action="{{ route('nodes.allocations.store', $node) }}">
            @csrf
            <div class="row">
                <div>
                    <label for="ip">IP</label>
                    <input type="text" name="ip" id="ip" placeholder="{{ $node->fqdn }}" required>
                </div>
                <div>
                    <label for="port_start">Port Awal</label>
                    <input type="number" name="port_start" id="port_start" placeholder="7777" required>
                </div>
                <div>
                    <label for="port_end">Port Akhir (opsional, buat range)</label>
                    <input type="number" name="port_end" id="port_end" placeholder="7787">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">+ Tambah Allocation</button>
        </form>
    </div>
@endsection
