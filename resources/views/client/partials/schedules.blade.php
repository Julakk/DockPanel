<div class="dp-card">
    <strong>Schedules</strong>
    <p class="dp-muted">Jalankan aksi otomatis pakai format cron (menit jam tanggal bulan hari). Waktu mengikuti timezone panel.</p>

    @forelse ($server->schedules as $s)
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;justify-content:space-between;align-items:center;border-top:1px solid rgba(127,127,127,.25);padding:.5rem 0">
            <div>
                <strong>{{ $s->name }}</strong>
                @unless ($s->is_active) <span class="dp-muted">(nonaktif)</span> @endunless
                <div class="dp-muted">
                    {{ $s->action }}{{ $s->payload ? ': '.$s->payload : '' }} · <code>{{ $s->cron }}</code>
                    @if ($s->last_run_at) · terakhir {{ $s->last_run_at->diffForHumans() }} ({{ $s->last_status }}) @endif
                </div>
            </div>
            <div style="display:flex;gap:.4rem">
                <form method="POST" action="{{ route('client.servers.schedules.toggle', [$server, $s]) }}">
                    @csrf @method('PUT')
                    <button class="dp-btn" type="submit">{{ $s->is_active ? 'Matikan' : 'Aktifkan' }}</button>
                </form>
                <form method="POST" action="{{ route('client.servers.schedules.destroy', [$server, $s]) }}" onsubmit="return confirm('Hapus schedule ini?')">
                    @csrf @method('DELETE')
                    <button class="dp-btn" type="submit">Hapus</button>
                </form>
            </div>
        </div>
    @empty
        <p class="dp-muted">Belum ada schedule.</p>
    @endforelse
</div>

<form method="POST" action="{{ route('client.servers.schedules.store', $server) }}" class="dp-card" style="display:grid;gap:.6rem">
    @csrf
    <strong>Tambah schedule</strong>
    <input class="dp-input" name="name" placeholder="Nama, mis. Restart harian" maxlength="60" value="{{ old('name') }}" required>
    <select class="dp-input" name="action" required>
        @foreach (['restart' => 'Restart', 'start' => 'Start', 'stop' => 'Stop', 'kill' => 'Kill', 'command' => 'Kirim command'] as $v => $l)
            <option value="{{ $v }}" @selected(old('action') === $v)>{{ $l }}</option>
        @endforeach
    </select>
    <input class="dp-input" name="payload" placeholder="Command (hanya untuk 'Kirim command')" maxlength="255" value="{{ old('payload') }}">
    <input class="dp-input" name="cron" list="dp-cron-presets" placeholder="0 4 * * *" value="{{ old('cron') }}" required>
    <datalist id="dp-cron-presets">
        <option value="0 4 * * *">Tiap hari jam 04:00</option>
        <option value="*/30 * * * *">Tiap 30 menit</option>
        <option value="0 */6 * * *">Tiap 6 jam</option>
        <option value="0 4 * * 1">Tiap Senin jam 04:00</option>
    </datalist>
    <button class="dp-btn" type="submit">Tambah</button>
</form>
