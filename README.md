# DockPanel

> Self-hosted game server management panel. Terinspirasi dari Pterodactyl, dibangun dari nol pakai Laravel.

Dikembangkan oleh **Julak Junior** ([@Julakk](https://github.com/Julakk)) — dicoding langsung dari HP via Termux. 🐧📱

---

## Arsitektur

DockPanel punya 2 komponen terpisah:

```
┌───────────────────┐   HTTP + JWT    ┌──────────────────────┐
│   PANEL (repo ini)  │ ◄─────────────► │   WINGS (daemon Go)   │
│   Laravel 11        │                 │   Docker control      │
│   MySQL + Redis      │                 │   SFTP + WebSocket     │
└───────────────────┘                 └──────────────────────┘
```

- **Panel** (repo ini): web UI, auth, database user/server/egg, kirim perintah ke node.
- **Wings**: daemon yang beneran jalan di tiap node/VPS, spawn Docker container per server game. **Belum diimplementasi di repo ini** — untuk sementara pakai Wings resmi dari Pterodactyl (kompatibel protokol) sambil Panel-nya matang.

## Struktur Data Inti

| Tabel | Fungsi |
|---|---|
| `nodes` | Server fisik/VPS yang jalanin Wings |
| `nests` | Kategori game (Minecraft, SA-MP, FiveM, dst) |
| `eggs` | Template docker image + startup command per game |
| `egg_variables` | Variabel yang bisa diisi user per egg (ex: `SERVER_JARFILE`) |
| `servers` | Instance server game milik user |
| `server_variables` | Nilai variable egg yang di-set per server |
| `allocations` | Kombinasi IP:port yang di-assign ke server |
| `server_subusers` | Akses terbatas user lain ke satu server |

## Fitur yang Udah Jalan

- 🔐 **Auth** — login/logout, dashboard, proteksi khusus admin (`root_admin` middleware)
- 🖥️ **CRUD Node** — kelola VPS/node, auto-generate daemon token
- 🔌 **Allocation Management** — tambah range IP:port per node (max 100 port sekaligus), assign/lepas dari server
- 🌐 **CRUD Nest** — kategori game
- 🥚 **CRUD Egg** — template docker image + startup command, manage variable per egg, **import dari JSON kompatibel format Pterodactyl**
- 📦 **CRUD Server** — nyatuin Node + Nest/Egg + Allocation jadi satu instance server, isi variable per server, tombol provisioning ke Wings
- ⚙️ **CI otomatis** (GitHub Actions) — install dependency, migrate, code style check (Pint), test (PHPUnit) tiap push

Semua fitur di atas 100% bisa dites tanpa VPS — murni Laravel + database, nggak nyentuh Docker sama sekali.

## Alur Pemakaian

1. Login sebagai admin
2. Bikin **Node** (VPS/server fisik)
3. Tambah **Allocation** (IP:port) di halaman detail Node
4. Bikin **Nest** (kategori game) dan **Egg** (template startup), atau import Egg dari JSON
5. Bikin **Server** — pilih owner, node, egg, allocation, resource limit
6. Isi **Variable** server (mis. `SERVER_JARFILE`)
7. Klik **Provision ke Wings** — bakal gagal graceful sampai ada VPS dengan Wings aktif

## Testing Wings (Docker control)

⚠️ **Docker nggak bisa jalan di Termux/Android.** Bagian ini WAJIB ditest di VPS/server Linux beneran (bisa pakai salah satu node Pterodactyl yang udah ada).

Development flow yang disarankan:

```
Termux (nulis kode Panel) → git push → GitHub Actions (test otomatis)
                                              │
                                              ▼
                                   deploy ke VPS (Wings jalan di sini)
```

## Roadmap

- [x] Skeleton migration + model (nodes, servers, eggs, nests, allocations)
- [x] `WingsService` — service class buat komunikasi ke daemon
- [x] Auth + role admin/user
- [x] CRUD Node (admin) + Allocation management
- [x] CRUD Egg/Nest + import format JSON (kompatibel format Pterodactyl)
- [x] CRUD Server (admin-facing) + provisioning skeleton
- [ ] WebSocket console real-time — **butuh VPS**
- [ ] File manager (proxy ke SFTP Wings) — **butuh VPS**
- [ ] Testing `WingsService` ke daemon Wings asli — **butuh VPS**
- [ ] Billing/expiry integration (opsional, buat dipakai di Ahmad Store)

## License

MIT
