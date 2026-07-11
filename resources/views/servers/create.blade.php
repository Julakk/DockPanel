@extends('layouts.app')

@section('title', 'Buat Server - DockPanel')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Dashboard</a><span class="sep">/</span>
    <a href="{{ route('servers.index') }}">Servers</a><span class="sep">/</span>Buat
@endsection

@section('content')
    <h2>Buat Server Baru</h2>

    <div class="card">
        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        @endif

        @if ($nodes->isEmpty() || $eggs->isEmpty())
            <p class="error">
                @if ($nodes->isEmpty()) Belum ada Node. @endif
                @if ($eggs->isEmpty()) Belum ada Egg. @endif
                Bikin dulu sebelum bisa buat server.
            </p>
        @else
            <form method="POST" action="{{ route('servers.store') }}">
                @csrf

                <label for="name">Nama Server</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" placeholder="ex: SA-MP Server Julak" required>

                <label for="description">Deskripsi (opsional)</label>
                <textarea name="description" id="description" rows="2">{{ old('description') }}</textarea>

                <div class="row">
                    <div>
                        <label for="owner_id">Owner</label>
                        <select name="owner_id" id="owner_id" required>
                            <option value="">-- Pilih User --</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" {{ old('owner_id') == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="node_id">Node</label>
                        <select name="node_id" id="node_id" required>
                            <option value="">-- Pilih Node --</option>
                            @foreach ($nodes as $node)
                                <option value="{{ $node->id }}" {{ old('node_id') == $node->id ? 'selected' : '' }}>{{ $node->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <label for="egg_id">Egg</label>
                <select name="egg_id" id="egg_id" required>
                    <option value="">-- Pilih Egg --</option>
                    @foreach ($eggs as $egg)
                        <option value="{{ $egg->id }}" {{ old('egg_id') == $egg->id ? 'selected' : '' }}>{{ $egg->nest->name }} / {{ $egg->name }}</option>
                    @endforeach
                </select>

                <label for="allocation_id">Allocation / Port (opsional)</label>
                <select name="allocation_id" id="allocation_id">
                    <option value="">-- Tanpa allocation dulu --</option>
                    @foreach ($allocations as $alloc)
                        <option value="{{ $alloc->id }}" {{ old('allocation_id') == $alloc->id ? 'selected' : '' }}>{{ $alloc->node->name }} — {{ $alloc->ip }}:{{ $alloc->port }}</option>
                    @endforeach
                </select>
                @if ($allocations->isEmpty())
                    <p class="muted" style="margin-top:-0.6rem;">Belum ada allocation available — tambah dulu di halaman detail Node.</p>
                @endif

                <div class="row">
                    <div>
                        <label for="memory">Memory (MB)</label>
                        <input type="number" name="memory" id="memory" value="{{ old('memory', 1024) }}" required>
                    </div>
                    <div>
                        <label for="swap">Swap (MB)</label>
                        <input type="number" name="swap" id="swap" value="{{ old('swap', 0) }}" required>
                    </div>
                </div>

                <div class="row">
                    <div>
                        <label for="disk">Disk (MB)</label>
                        <input type="number" name="disk" id="disk" value="{{ old('disk', 5120) }}" required>
                    </div>
                    <div>
                        <label for="cpu">CPU Limit (%, 0 = unlimited)</label>
                        <input type="number" name="cpu" id="cpu" value="{{ old('cpu', 100) }}" required>
                    </div>
                </div>

                <label for="io">IO Weight (10-1000)</label>
                <input type="number" name="io" id="io" value="{{ old('io', 500) }}" required>

                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">Buat Server</button>
                    <a href="{{ route('servers.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        @endif
    </div>
@endsection
