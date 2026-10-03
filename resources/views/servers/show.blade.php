@extends('layouts.app')

@section('title', $server->name . ' - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>
    <a href="{{ route('servers.index') }}">Servers</a><span class="sep">&gt;</span>{{ $server->name }}
@endsection

@section('content')
    @php
        $primary = $server->allocations->firstWhere('is_primary', true) ?? $server->allocations->first();
        $assignedMountIds = $server->mounts->pluck('id')->toArray();
        $stateKey = $server->suspended ? 'suspended' : $server->status;
    @endphp

    <h2 class="nd-title">{{ $server->name }}
        <span class="status-badge status-{{ $stateKey }}" style="vertical-align:middle;">{{ $stateKey }}</span>
        @if ($server->description)<small>{{ $server->description }}</small>@endif
    </h2>

    @if ($errors->any())
        <div class="error">
            @foreach ($errors->all() as $error){{ $error }}<br>@endforeach
        </div>
    @endif

    <div class="nd-tabs">
        <a href="#about" data-tab="about" class="active">About</a>
        <a href="#details" data-tab="details">Details</a>
        <a href="#build" data-tab="build">Build Configuration</a>
        <a href="#startup" data-tab="startup">Startup</a>
        <a href="#database" data-tab="database">Database</a>
        <a href="#mounts" data-tab="mounts">Mounts</a>
        <a href="#subusers" data-tab="subusers">Subusers</a>
        <a href="#manage" data-tab="manage">Manage</a>
        <a href="#delete" data-tab="delete">Delete</a>
        <a href="{{ route('client.servers.show', $server) }}" target="_blank" rel="noopener" title="Buka sebagai user">&#8599;</a>
    </div>

    {{-- ===== ABOUT ===== --}}
    <div class="nd-pane" id="pane-about">
        <div class="nd-grid">
            <div class="nd-box">
                <div class="nd-box-head"><h3>Information</h3></div>
                <div class="nd-box-body nd-flush">
                    <table class="nd-table nd-kv">
                        <tr><td>Internal Identifier</td><td><span class="nd-code">{{ $server->id }}</span></td></tr>
                        <tr><td>Short ID</td><td><span class="nd-code">{{ $server->uuid_short }}</span></td></tr>
                        <tr><td>UUID / Docker Container ID</td><td><span class="nd-code" style="word-break:break-all;">{{ $server->uuid }}</span></td></tr>
                        <tr><td>Current Egg</td><td>{{ $server->egg?->nest?->name ?? '-' }} :: {{ $server->egg?->name ?? '-' }}</td></tr>
                        <tr><td>Server Name</td><td>{{ $server->name }}</td></tr>
                        <tr><td>CPU Limit</td><td><span class="nd-code">{{ $server->cpu ? $server->cpu.'%' : 'Unlimited' }}</span></td></tr>
                        <tr><td>Memory</td><td><span class="nd-code">{{ $server->memory ? number_format($server->memory).' MiB' : 'Unlimited' }}</span> / <span class="nd-code">Swap {{ $server->swap }} MiB</span></td></tr>
                        <tr><td>Disk Space</td><td><span class="nd-code">{{ $server->disk ? number_format($server->disk).' MiB' : 'Unlimited' }}</span></td></tr>
                        <tr><td>Block IO Weight</td><td><span class="nd-code">{{ $server->io }}</span></td></tr>
                        <tr><td>Default Connection</td><td>@if ($primary)<span class="nd-code">{{ $primary->ip }}:{{ $primary->port }}</span>@else<span class="nd-mute">-</span>@endif</td></tr>
                        <tr><td>Docker Image</td><td><span class="nd-code" style="word-break:break-all;">{{ $server->image }}</span></td></tr>
                        @if ($server->expires_at)
                            <tr><td>Expired</td><td>{{ $server->expires_at->format('d M Y H:i') }} <span class="nd-mute">({{ $server->expires_at->diffForHumans() }})</span></td></tr>
                        @endif
                    </table>
                </div>
            </div>

            <div>
                <div class="nd-who">
                    <div class="nd-who-top">
                        <div>
                            <div class="nd-who-name">{{ $server->owner->name }}</div>
                            <div class="nd-who-sub">Server Owner</div>
                        </div>
                        <svg viewBox="0 0 24 24" width="46" height="46" fill="currentColor"><circle cx="12" cy="8" r="4.5"/><path d="M3.5 21c0-4.4 3.8-7 8.5-7s8.5 2.6 8.5 7z"/></svg>
                    </div>
                    <a href="{{ route('users.edit', $server->owner) }}" class="nd-who-more">More info &rarr;</a>
                </div>
                <div class="nd-who">
                    <div class="nd-who-top">
                        <div>
                            <div class="nd-who-name">{{ $server->node->name }}</div>
                            <div class="nd-who-sub">Server Node</div>
                        </div>
                        <svg viewBox="0 0 24 24" width="46" height="46" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3l9 5-9 5-9-5z"/><path d="M3 12l9 5 9-5M3 16l9 5 9-5"/></svg>
                    </div>
                    <a href="{{ route('nodes.show', $server->node) }}" class="nd-who-more">More info &rarr;</a>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== DETAILS ===== --}}
    <div class="nd-pane" id="pane-details">
        <div class="nd-box">
            <div class="nd-box-head"><h3>Base Information</h3></div>
            <div class="nd-box-body">
                <form method="POST" action="{{ route('servers.update', $server) }}">
                    @csrf
                    @method('PUT')

                    <label for="name">Server Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $server->name) }}" required>

                    <label for="owner_id">Server Owner</label>
                    <select name="owner_id" id="owner_id" required>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ old('owner_id', $server->owner_id) == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>

                    <label for="description">Server Description</label>
                    <textarea name="description" id="description" rows="3">{{ old('description', $server->description) }}</textarea>
                    <div class="nd-hint">Catatan singkat tentang server ini.</div>

                    <button type="submit" class="nd-btn nd-btn-blue">Update Details</button>
                </form>
            </div>
        </div>
    </div>

    {{-- ===== BUILD CONFIGURATION ===== --}}
    <div class="nd-pane" id="pane-build">
        <div class="nd-split">
            <div class="nd-box">
                <div class="nd-box-head"><h3>Resource Management</h3></div>
                <div class="nd-box-body">
                    <form method="POST" action="{{ route('servers.update', $server) }}">
                        @csrf
                        @method('PUT')

                        <label for="cpu">CPU Limit (%)</label>
                        <input type="number" step="any" name="cpu" id="cpu" value="{{ old('cpu', $server->cpu) }}" required>
                        <div class="nd-hint">Satu core dihitung 100%. Isi 0 buat tanpa batas.</div>

                        <label for="memory">Allocated Memory (MiB)</label>
                        <input type="number" name="memory" id="memory" value="{{ old('memory', $server->memory) }}" required>
                        <div class="nd-hint">Isi 0 buat memory tanpa batas.</div>

                        <label for="swap">Allocated Swap (MiB)</label>
                        <input type="number" name="swap" id="swap" value="{{ old('swap', $server->swap) }}" required>
                        <div class="nd-hint">Isi 0 buat mematikan swap.</div>

                        <label for="disk">Disk Space Limit (MiB)</label>
                        <input type="number" name="disk" id="disk" value="{{ old('disk', $server->disk) }}" required>
                        <div class="nd-hint">Isi 0 buat disk tanpa batas.</div>

                        <label for="io">Block IO Proportion</label>
                        <input type="number" name="io" id="io" value="{{ old('io', $server->io) }}" required>
                        <div class="nd-hint">Nilai antara 10 dan 1000.</div>

                        <button type="submit" class="nd-btn nd-btn-blue">Update Build Configuration</button>
                    </form>
                </div>
            </div>

            <div class="nd-box">
                <div class="nd-box-head"><h3>Allocation Management</h3></div>
                <div class="nd-box-body">
                    <form method="POST" action="{{ route('servers.allocations.update', $server) }}">
                        @csrf
                        @method('PUT')
                        @if ($nodeAllocations->isEmpty())
                            <p class="nd-mute" style="margin:0;">Belum ada allocation kosong di node ini. Tambah dulu lewat halaman Node.</p>
                        @else
                            @foreach ($nodeAllocations as $alloc)
                                <label style="display:block;font-weight:normal;margin-bottom:.4rem;">
                                    <input type="checkbox" name="allocation_ids[]" value="{{ $alloc->id }}" style="width:auto;"
                                        {{ $server->allocations->contains('id', $alloc->id) ? 'checked' : '' }}>
                                    <span class="nd-code">{{ $alloc->ip }}:{{ $alloc->port }}</span>
                                    <input type="radio" name="primary_allocation_id" value="{{ $alloc->id }}" style="width:auto;margin-left:.5rem;"
                                        {{ $alloc->is_primary ? 'checked' : '' }}> primary
                                </label>
                            @endforeach
                            <div class="nd-hint">Centang port yang dipakai server ini, lalu pilih satu sebagai primary (koneksi utama).</div>
                            <button type="submit" class="nd-btn nd-btn-blue">Update Allocation</button>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== STARTUP ===== --}}
    <div class="nd-pane" id="pane-startup">
        <div class="nd-box">
            <div class="nd-box-head"><h3>Startup Command</h3></div>
            <div class="nd-box-body">
                <code style="display:block;white-space:pre-wrap;word-break:break-all;background:rgba(0,0,0,.25);padding:.6rem;border-radius:3px;">{{ $server->startup }}</code>
                <div class="nd-hint">Command startup ditentuin dari Egg dan belum bisa diubah dari halaman ini.</div>
            </div>
        </div>

        <div class="nd-split" style="margin-bottom:1rem;">
            <div class="nd-box">
                <div class="nd-box-head"><h3>Service Configuration</h3></div>
                <div class="nd-box-body nd-flush">
                    <table class="nd-table nd-kv">
                        <tr><td>Nest</td><td>{{ $server->egg?->nest?->name ?? '-' }}</td></tr>
                        <tr><td>Egg</td><td>{{ $server->egg?->name ?? '-' }}</td></tr>
                        <tr><td>Skip Egg Install Script</td><td>{{ $server->skip_scripts ? 'Ya' : 'Tidak' }}</td></tr>
                    </table>
                </div>
            </div>
            <div class="nd-box">
                <div class="nd-box-head"><h3>Docker Image Configuration</h3></div>
                <div class="nd-box-body">
                    <span class="nd-code" style="word-break:break-all;">{{ $server->image }}</span>
                </div>
            </div>
        </div>

        <div class="nd-box">
            <div class="nd-box-head"><h3>Variables</h3></div>
            <div class="nd-box-body">
                @if ($server->serverVariables->isEmpty())
                    <p class="nd-mute" style="margin:0;">Egg ini nggak punya variable.</p>
                @else
                    <form method="POST" action="{{ route('servers.variables.update', $server) }}">
                        @csrf
                        @method('PUT')
                        @foreach ($server->serverVariables as $sv)
                            <label for="var_{{ $sv->egg_variable_id }}">{{ $sv->eggVariable->name }}</label>
                            <input type="text" name="variables[{{ $sv->egg_variable_id }}]" id="var_{{ $sv->egg_variable_id }}"
                                value="{{ $sv->variable_value }}" placeholder="{{ $sv->eggVariable->default_value }}">
                            <div class="nd-hint">Startup Command Variable: <span class="nd-code">{{ $sv->eggVariable->env_variable }}</span></div>
                        @endforeach
                        <button type="submit" class="nd-btn nd-btn-blue">Save Variables</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- ===== DATABASE ===== --}}
    <div class="nd-pane" id="pane-database">
        <div class="nd-split">
            <div class="nd-box">
                <div class="nd-box-head"><h3>Active Databases</h3></div>
                <div class="nd-box-body nd-flush">
                    @if ($server->databases->isEmpty())
                        <p class="nd-mute" style="margin:.9rem;">Belum ada database buat server ini.</p>
                    @else
                        <div class="nd-wrap">
                            <table class="nd-table">
                                <thead><tr><th>Database</th><th>Username</th><th>Host</th><th></th></tr></thead>
                                <tbody>
                                    @foreach ($server->databases as $db)
                                        <tr>
                                            <td>{{ $db->database }}</td>
                                            <td class="nd-mute">{{ $db->username }}</td>
                                            <td class="nd-mute">{{ $db->databaseHost->name ?? '-' }}</td>
                                            <td>
                                                <form method="POST" action="{{ route('servers.databases.destroy', [$server, $db]) }}" onsubmit="return confirm('Hapus database ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="nd-btn nd-btn-red">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="nd-box nd-top-green">
                <div class="nd-box-head"><h3>Create New Database</h3></div>
                <div class="nd-box-body">
                    @if ($databaseHosts->isEmpty())
                        <p class="nd-mute" style="margin:0;">Belum ada Database Host. Tambah dulu di halaman <a href="{{ route('databases.index') }}">Databases</a>.</p>
                    @else
                        <form method="POST" action="{{ route('servers.databases.store', $server) }}">
                            @csrf
                            <label for="database_host_id">Database Host</label>
                            <select name="database_host_id" id="database_host_id" required>
                                @foreach ($databaseHosts as $dbHost)
                                    <option value="{{ $dbHost->id }}">{{ $dbHost->name }}</option>
                                @endforeach
                            </select>
                            <div class="nd-hint">Host database tempat database ini dibuat.</div>

                            <label for="database_name">Database</label>
                            <input type="text" name="database_name" id="database_name" placeholder="mygame_db" required>
                            <div class="nd-hint">Username dan password dibuat acak setelah form dikirim.</div>

                            <button type="submit" class="nd-btn nd-btn-green">Create Database</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ===== MOUNTS ===== --}}
    <div class="nd-pane" id="pane-mounts">
        <div class="nd-box">
            <div class="nd-box-head"><h3>Available Mounts</h3></div>
            <div class="nd-box-body nd-flush">
                @if ($allMounts->isEmpty())
                    <p class="nd-mute" style="margin:.9rem;">Belum ada mount point. Tambah dulu di halaman <a href="{{ route('mounts.index') }}">Mounts</a>.</p>
                @else
                    <form method="POST" action="{{ route('servers.mounts.update', $server) }}">
                        @csrf
                        @method('PUT')
                        <div class="nd-wrap">
                            <table class="nd-table">
                                <thead><tr><th>ID</th><th>Name</th><th>Source</th><th>Target</th><th class="c">Status</th></tr></thead>
                                <tbody>
                                    @foreach ($allMounts as $mount)
                                        <tr>
                                            <td><span class="nd-code">{{ $mount->id }}</span></td>
                                            <td>{{ $mount->name }}</td>
                                            <td class="nd-mute">{{ $mount->source }}</td>
                                            <td class="nd-mute">{{ $mount->target }}</td>
                                            <td class="c"><input type="checkbox" name="mount_ids[]" value="{{ $mount->id }}" style="width:auto;" {{ in_array($mount->id, $assignedMountIds) ? 'checked' : '' }}></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div style="padding:.9rem;"><button type="submit" class="nd-btn nd-btn-blue">Save Mounts</button></div>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- ===== SUBUSERS ===== --}}
    <div class="nd-pane" id="pane-subusers">
        <div class="nd-box">
            <div class="nd-box-head"><h3>Subusers</h3></div>
            <div class="nd-box-body nd-flush">
                <p class="nd-hint" style="margin:.9rem;">Kasih akses server ini ke user lain tanpa jadiin mereka owner.</p>
                @if ($server->subusers->isEmpty())
                    <p class="nd-mute" style="margin:.9rem;">Belum ada subuser.</p>
                @else
                    <div class="nd-wrap">
                        <table class="nd-table">
                            <thead><tr><th>Nama</th><th>Email</th><th>Permissions</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($server->subusers as $subuser)
                                    <tr>
                                        <td>{{ $subuser->name }}</td>
                                        <td class="nd-mute">{{ $subuser->email }}</td>
                                        <td class="nd-mute">{{ implode(', ', json_decode($subuser->pivot->permissions ?? '[]')) ?: '-' }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('servers.subusers.destroy', [$server, $subuser]) }}" onsubmit="return confirm('Cabut akses subuser ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="nd-btn nd-btn-red">Cabut</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="nd-box nd-top-green">
            <div class="nd-box-head"><h3>Tambah Subuser</h3></div>
            <div class="nd-box-body">
                <form method="POST" action="{{ route('servers.subusers.store', $server) }}">
                    @csrf
                    <label for="subuser_email">Email User</label>
                    <input type="email" name="email" id="subuser_email" placeholder="user@example.com" required>

                    <label style="margin-top:.6rem;">Permissions</label>
                    @foreach ($availablePermissions as $key => $label)
                        <label style="display:flex;align-items:center;gap:.5rem;margin-bottom:.4rem;font-weight:normal;">
                            <input type="checkbox" name="permissions[]" value="{{ $key }}" style="width:auto;">
                            <span style="font-size:.85rem;">{{ $label }}</span>
                        </label>
                    @endforeach

                    <button type="submit" class="nd-btn nd-btn-green" style="margin-top:.6rem;">Tambah Subuser</button>
                </form>
            </div>
        </div>
    </div>

    {{-- ===== MANAGE ===== --}}
    <div class="nd-pane" id="pane-manage">
        <div class="nd-cards">
            <div class="nd-box nd-danger">
                <div class="nd-box-head"><h3>Reinstall Server</h3></div>
                <div class="nd-box-body">
                    <p style="margin:0 0 .9rem;">Install ulang server dengan script dari Egg. Data server bisa tertimpa.</p>
                    <button type="button" class="nd-btn nd-btn-red" disabled>Belum tersedia</button>
                </div>
            </div>

            <div class="nd-box nd-top-blue">
                <div class="nd-box-head"><h3>Install Status</h3></div>
                <div class="nd-box-body">
                    <p style="margin:0 0 .9rem;">Ubah status install server secara manual.</p>
                    <button type="button" class="nd-btn nd-btn-blue" disabled>Belum tersedia</button>
                </div>
            </div>

            <div class="nd-box nd-top-orange">
                <div class="nd-box-head"><h3>{{ $server->suspended ? 'Unsuspend Server' : 'Suspend Server' }}</h3></div>
                <div class="nd-box-body">
                    @if ($server->suspended)
                        <p style="margin:0 0 .9rem;">Server lagi disuspend{{ $server->suspension_reason ? ' ('.$server->suspension_reason.')' : '' }}. Aktifkan lagi supaya user bisa mengelolanya.</p>
                        <form method="POST" action="{{ route('servers.unsuspend', $server) }}">
                            @csrf
                            <button type="submit" class="nd-btn nd-btn-orange">Unsuspend Server</button>
                        </form>
                    @else
                        <p style="margin:0 0 .9rem;">Server dihentikan dan user langsung dicegah ngakses file atau ngelola server lewat panel.</p>
                        <form method="POST" action="{{ route('servers.suspend', $server) }}" onsubmit="return confirm('Suspend server ini? Server akan dihentikan.');">
                            @csrf
                            <button type="submit" class="nd-btn nd-btn-orange">Suspend Server</button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="nd-box nd-top-green">
                <div class="nd-box-head"><h3>Provision ke Wings</h3></div>
                <div class="nd-box-body">
                    <p style="margin:0 0 .6rem;">Status sekarang: <span class="status-badge status-{{ $server->status }}">{{ $server->status }}</span></p>
                    <p class="nd-hint" style="margin:0 0 .9rem;">Gagal kalau node belum punya Wings yang aktif.</p>
                    <form method="POST" action="{{ route('servers.provision', $server) }}" onsubmit="return confirm('Provision server ke Wings sekarang?');">
                        @csrf
                        <button type="submit" class="nd-btn nd-btn-green">Provision ke Wings</button>
                    </form>
                </div>
            </div>

            <div class="nd-box nd-top-green">
                <div class="nd-box-head"><h3>Transfer Server</h3></div>
                <div class="nd-box-body">
                    <p style="margin:0 0 .9rem;">Pindahin server ini ke node lain.</p>
                    <button type="button" class="nd-btn nd-btn-green" disabled>Belum tersedia</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== DELETE ===== --}}
    <div class="nd-pane" id="pane-delete">
        <div class="nd-box nd-danger">
            <div class="nd-box-head"><h3>Delete Server</h3></div>
            <div class="nd-box-body">
                <p style="margin:0 0 .6rem;">Aksi ini menghapus server dari panel.</p>
                <p class="nd-bad" style="margin:0 0 .9rem;font-size:.8rem;">Menghapus server tidak bisa dibatalkan. Semua data server dan akses user ke server ini ikut hilang.</p>
                <form method="POST" action="{{ route('servers.destroy', $server) }}" onsubmit="return confirm('Yakin hapus server ini permanen?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="nd-btn nd-btn-red">Delete This Server</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var key = 'dp-srv-tab-{{ $server->id }}';
            var tabs = document.querySelectorAll('.nd-tabs [data-tab]');
            var panes = document.querySelectorAll('.nd-pane');

            function show(id) {
                if (!document.getElementById('pane-' + id)) { id = 'about'; }
                panes.forEach(function (p) { p.classList.toggle('nd-hide', p.id !== 'pane-' + id); });
                tabs.forEach(function (t) { t.classList.toggle('active', t.dataset.tab === id); });
                try { sessionStorage.setItem(key, id); } catch (e) {}
            }

            tabs.forEach(function (t) {
                t.addEventListener('click', function (ev) {
                    ev.preventDefault();
                    show(t.dataset.tab);
                    history.replaceState(null, '', '#' + t.dataset.tab);
                });
            });

            var start = location.hash.replace('#', '');
            if (!start && document.referrer.indexOf('/servers/{{ $server->id }}') > -1) {
                try { start = sessionStorage.getItem(key) || ''; } catch (e) {}
            }
            show(start || 'about');
        })();
    </script>
@endsection
