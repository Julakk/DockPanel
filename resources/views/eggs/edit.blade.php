@extends('layouts.app')

@section('title', 'Edit Egg - DockPanel')

@section('content')
    <h2>Edit Egg: {{ $egg->name }}</h2>

    <div class="card">
        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('eggs.update', $egg) }}">
            @csrf
            @method('PUT')

            <label for="nest_id">Nest</label>
            <select name="nest_id" id="nest_id" required>
                @foreach ($nests as $nest)
                    <option value="{{ $nest->id }}" {{ old('nest_id', $egg->nest_id) == $nest->id ? 'selected' : '' }}>{{ $nest->name }}</option>
                @endforeach
            </select>

            <label for="name">Nama Egg</label>
            <input type="text" name="name" id="name" value="{{ old('name', $egg->name) }}" required>

            <label for="description">Deskripsi (opsional)</label>
            <textarea name="description" id="description" rows="2">{{ old('description', $egg->description) }}</textarea>

            <label for="docker_image">Docker Image</label>
            <input type="text" name="docker_image" id="docker_image" value="{{ old('docker_image', $egg->docker_image) }}" required>

            <label for="startup">Startup Command</label>
            <textarea name="startup" id="startup" rows="2" required>{{ old('startup', $egg->startup) }}</textarea>
            <p class="muted" style="margin-top:-0.6rem;">Pakai @{{VAR_NAME}} buat placeholder variable.</p>

            <label for="script_container">Install Script Container</label>
            <input type="text" name="script_container" id="script_container" value="{{ old('script_container', $egg->script_container) }}">

            <label for="script_install">Install Script (opsional)</label>
            <textarea name="script_install" id="script_install" rows="4">{{ old('script_install', $egg->script_install) }}</textarea>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Update Egg</button>
                <a href="{{ route('eggs.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Variables</h3>

        @if ($egg->variables->isEmpty())
            <p class="muted">Belum ada variable. Tambah di bawah, contoh: <code>SERVER_JARFILE</code>.</p>
        @else
            <table style="margin-bottom:1.5rem;">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>ENV Variable</th>
                        <th>Default</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($egg->variables as $variable)
                        <tr>
                            <td>{{ $variable->name }}</td>
                            <td class="muted">{{ '{{' . $variable->env_variable . '}}' }}</td>
                            <td class="muted">{{ $variable->default_value ?: '-' }}</td>
                            <td class="actions">
                                <form method="POST" action="{{ route('eggs.variables.destroy', [$egg, $variable]) }}" onsubmit="return confirm('Hapus variable ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <form method="POST" action="{{ route('eggs.variables.store', $egg) }}">
            @csrf
            <div class="row">
                <div>
                    <label for="var_name">Nama Tampilan</label>
                    <input type="text" name="name" id="var_name" placeholder="Server Jar File">
                </div>
                <div>
                    <label for="env_variable">ENV Variable</label>
                    <input type="text" name="env_variable" id="env_variable" placeholder="SERVER_JARFILE">
                </div>
            </div>
            <label for="default_value">Default Value</label>
            <input type="text" name="default_value" id="default_value" placeholder="server.jar">
            <label for="rules">Validation Rules</label>
            <input type="text" name="rules" id="rules" value="nullable|string" placeholder="required|string|max:255">
            <button type="submit" class="btn btn-primary">+ Tambah Variable</button>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top:0;color:#f87171;">Zona Bahaya</h3>
        <form method="POST" action="{{ route('eggs.destroy', $egg) }}" onsubmit="return confirm('Yakin hapus egg ini?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Hapus Egg</button>
        </form>
    </div>
@endsection
