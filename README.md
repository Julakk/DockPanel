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

## Setup Development

### 1. Requirement
- PHP 8.3+
- Composer
- MySQL/MariaDB
- Redis

### 2. Install (Termux atau VPS)

```bash
pkg install php php-gd composer mariadb redis   # kalau di Termux
# atau: apt install php8.3 composer mariadb-server redis-server  # kalau di VPS/Debian

git clone https://github.com/Julakk/DockPanel.git
cd DockPanel

composer install
cp .env.example .env
php artisan key:generate

# nyalain mysql & redis dulu, buat database `dockpanel`
php artisan migrate

php artisan serve
```

### 3. Testing Wings (Docker control)

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
- [ ] Auth + role admin/user (Laravel Breeze/Sanctum)
- [ ] CRUD Node (admin)
- [ ] CRUD Egg/Nest + import format JSON (kompatibel format Pterodactyl)
- [ ] CRUD Server (admin + user-facing)
- [ ] WebSocket console real-time
- [ ] File manager (proxy ke SFTP Wings)
- [ ] Billing/expiry integration (opsional, buat dipakai di Ahmad Store)

## License

MIT
