@extends('layouts.app')

@section('title', 'Edit Server - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>
    <a href="{{ route('servers.index') }}">Servers</a><span class="sep">&gt;</span>{{ $server->name }}
@endsection

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="margin:0;">{{ $server->name }}</h2>
        <span class="muted">{{ $server->egg->name }} — {{ $server->node->name }}</span>
    </div>

    @if ($errors->any())
        <div class="error">
            @foreach ($errors->all() as $error)
                {{ $error }}<br>
            @endforeach
        </div>
    @endif

    <div class="card">
        <h3 style="margin-top:0;">Detail Server</h3>
        <form method="POST" action="{{ route('servers.update', $server) }}">
            @csrf
            @method('PUT')

            <label for="name">Nama Server</label>
            <input type="text" name="name" id="name" value="{{ old('name', $server->name) }}" required>

            <label for="description">Deskripsi</label>
            <textarea name="description" id="description" rows="2">{{ old('description', $server->description) }}</textarea>

            <label for="owner_id">Owner</label>
            <select name="owner_id" id="owner_id" required>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" {{ old('owner_id', $server->owner_id) == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>

            <div class="row">
                <div>
                    <label for="memory">Memory (MB)</label>
                    <input type="number" name="memory" id="memory" value="{{ old('memory', $server->memory) }}" required>
                </div>
                <div>
                    <label for="swap">Swap (MB)</label>
                    <input type="number" name="swap" id="swap" value="{{ old('swap', $server->swap) }}" required>
                </div>
            </div>

            <div class="row">
                <div>
                    <label for="disk">Disk (MB)</label>
                    <input type="number" name="disk" id="disk" value="{{ old('disk', $server->disk) }}" required>
                </div>
                <div>
                    <label for="cpu">CPU Limit (%)</label>
                    <input type="number" name="cpu" id="cpu" value="{{ old('cpu', $server->cpu) }}" required>
                </div>
            </div>

            <label for="io">IO Weight</label>
            <input type="number" name="io" id="io" value="{{ old('io', $server->io) }}" required>

            <button type="submit" class="btn btn-primary">Update Detail</button>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Startup Command</h3>
        <p class="muted" style="background:#0f172a;padding:0.7rem;border-radius:6px;">{{ $server->startup }}</p>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Variables</h3>

        @if ($server->serverVariables->isEmpty())
            <p class="muted">Egg ini nggak punya variable.</p>
        @else
            <form method="POST" action="{{ route('servers.variables.update', $server) }}">
                @csrf
                @method('PUT')

                @foreach ($server->serverVariables as $sv)
                    <label for="var_{{ $sv->egg_variable_id }}">
                        {{ $sv->eggVariable->name }}
                        <span class="muted">({{ '{{' . $sv->eggVariable->env_variable . '}}' }})</span>
                    </label>
                    <input
                        type="text"
                        name="variables[{{ $sv->egg_variable_id }}]"
                        id="var_{{ $sv->egg_variable_id }}"
                        value="{{ $sv->variable_value }}"
                        placeholder="{{ $sv->eggVariable->default_value }}"
                    >
                @endforeach

                <button type="submit" class="btn btn-primary">Simpan Variables</button>
            </form>
        @endif
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Provisioning</h3>
        <p class="muted">Status sekarang: <span class="status-badge status-{{ $server->status }}">{{ $server->status }}</span></p>
        <form method="POST" action="{{ route('servers.provision', $server) }}">
            @csrf
            <button type="submit" class="btn btn-primary">Provision ke Wings</button>
        </form>
        <p class="muted" style="margin-top:0.8rem;">
            Bakal gagal kalau node belum ada Wings aktif — itu normal sampai VPS tersedia.
        </p>
    </div>

    <div class="card">
        <h3 style="margin-top:0;color:#f87171;">Zona Bahaya</h3>
        <form method="POST" action="{{ route('servers.destroy', $server) }}" onsubmit="return confirm('Yakin hapus server ini?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Hapus Server</button>
        </form>
    </div>
@endsection
