# DockPanel

> Self-hosted game server management panel. Terinspirasi dari Pterodactyl, dibangun dari nol pakai Laravel.

Dikembangkan oleh **Julak Junior** ([@Julakk](https://github.com/Julakk)) — dicoding langsung dari HP via Termux. 🐧📱

---

## Arsitektur

DockPanel punya 2 komponen terpisah, di repo yang berbeda:

```
┌───────────────────┐   HTTP + JWT    ┌──────────────────────┐
│   PANEL (repo ini)  │ ◄─────────────► │   WINGS (daemon Go)   │
│   Laravel 11        │                 │   Docker control      │
│   MySQL/SQLite       │                 │   SFTP + WebSocket     │
└───────────────────┘                 └──────────────────────┘
```

- **Panel** (repo ini): web UI, auth, database user/server/egg, kirim perintah ke node.
- **Wings**: daemon yang beneran jalan di tiap node/VPS, spawn Docker container per server game. Repo terpisah: [Julakk/DockWings](https://github.com/Julakk/DockWings) — baru skeleton awal (routing, auth middleware, interface Docker stub), implementasi Docker asli masih nunggu VPS buat testing.

## Struktur Data Inti

| Tabel | Fungsi |
|---|---|
| `nodes` | Server fisik/VPS yang jalanin Wings |
| `locations` | Kategorisasi Node berdasarkan lokasi fisik |
| `nests` | Kategori game (Minecraft, SA-MP, FiveM, dst) |
| `eggs` | Template docker image + startup command per game |
| `egg_variables` | Variabel yang bisa diisi user per egg (ex: `SERVER_JARFILE`) |
| `servers` | Instance server game milik user |
| `server_variables` | Nilai variable egg yang di-set per server |
| `allocations` | Kombinasi IP:port yang di-assign ke server |
| `server_subusers` | Akses terbatas user lain ke satu server, dengan permission granular |
| `server_databases` | Database yang di-provision buat server tertentu |
| `database_hosts` | Host MySQL/MariaDB yang bisa dipakai server |
| `mounts` | Mount point tambahan buat server |
| `activity_logs` | Histori aktivitas user (login, ganti password, dst) |
| `panel_settings` | Konfigurasi panel (company name, requirement 2FA, dll) |

## Fitur yang Udah Jalan

**Auth & Keamanan**
- 🔐 Login/logout, proteksi khusus admin (`root_admin` middleware)
- 🔑 Forgot password — flow reset lengkap lewat email
- 📱 Two-Factor Authentication beneran (TOTP, RFC 6238) — kompatibel Google Authenticator/Authy
- 📜 Activity Log — histori login, ganti password/email, enable/disable 2FA

**Admin**
- 🖥️ CRUD Node + Allocation Management (range IP:port, max 100 sekaligus)
- 📍 Locations — kategorisasi Node
- 🌐 CRUD Nest + 🥚 CRUD Egg (import JSON kompatibel format Pterodactyl, manage variable)
- 📦 CRUD Server — nyatuin Node + Nest/Egg + Allocation, assign Database Host & Mount, provisioning ke Wings
- 👥 CRUD Users + role admin
- 🔧 Settings (company name, 2FA requirement, default language)
- 🔗 Application API (token Sanctum)
- 🗄️ Database Hosts + 📁 Mounts

**Client Area (user biasa)**
- 📋 My Servers — daftar server milik sendiri atau yang di-subuser-kan
- 👤 Account Settings, API Credentials personal, Two-Factor, Activity
- 🤝 Subusers — admin bisa kasih akses server ke user lain dengan permission granular

**UI/UX**
- 🎨 Sidebar navigasi ala Pterodactyl (Basic Administration / Management / Service Management), collapse jadi hamburger di HP, otomatis nyesuain menu berdasarkan role
- 🖌️ Design system pakai CSS custom properties — warna, tipografi, hover/focus state konsisten di semua halaman
- 📊 Resource bar placeholder (CPU/Memory/Disk) di server card, siap diisi data beneran begitu Wings aktif

**Infrastruktur**
- ⚙️ CI otomatis (GitHub Actions) — install dependency, migrate, code style check (Pint), test (PHPUnit)
- 🚀 One-command installer (`install.sh`) — mirip `pterodactyl-installer`, install Panel atau Node/Wings di VPS Ubuntu 24 tinggal `bash <(curl -s ...)`

Semua fitur di atas (kecuali Wings) 100% bisa dites tanpa VPS — murni Laravel + database, nggak nyentuh Docker sama sekali.

## Alur Pemakaian

1. Login sebagai admin
2. Bikin **Location** (opsional) dan **Node** (VPS/server fisik)
3. Tambah **Allocation** (IP:port) di halaman detail Node
4. Bikin **Nest** (kategori game) dan **Egg** (template startup), atau import Egg dari JSON
5. Bikin **Server** — pilih owner, node, egg, allocation, resource limit
6. Isi **Variable** server, assign **Database** & **Mount** kalau perlu, tambah **Subuser** kalau mau kasih akses ke user lain
7. Klik **Provision ke Wings** — bakal gagal graceful sampai ada VPS dengan Wings aktif

## Setup Development (Termux)

Database default development pakai **SQLite** (nggak perlu nyalain service MySQL manual tiap sesi):

```bash
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## Install ke VPS Produksi

```bash
bash <(curl -s https://raw.githubusercontent.com/Julakk/DockPanel/main/install.sh)
```

Pilih opsi **1** buat install Panel, atau opsi **2** buat install Node/Wings di VPS Ubuntu 24 yang beda. Installer otomatis setup PHP, MariaDB, Nginx (buat Panel) atau Docker + Go (buat Wings).

## Testing Wings (Docker control)

⚠️ **Docker nggak bisa jalan di Termux/Android.** Bagian ini WAJIB ditest di VPS/server Linux beneran.

Development flow yang disarankan:

```
Termux (nulis kode Panel) → git push → GitHub Actions (test otomatis)
                                              │
                                              ▼
                                   deploy ke VPS (Wings jalan di sini)
```

## Roadmap

- [x] Skeleton migration + model, `WingsService`
- [x] Auth + role admin/user + 2FA + Forgot Password
- [x] CRUD Node/Nest/Egg/Server + Allocation
- [x] Halaman admin lengkap (Users, Locations, Settings, Application API, Databases, Mounts)
- [x] Assign Database Host & Mount ke Server
- [x] Client Area buat user biasa
- [x] Subusers + Activity Log
- [x] Redesign UI total — sidebar, design token, hover/focus state
- [x] Skeleton repo `DockWings` (Go)
- [x] One-command installer script (Panel + Wings)
- [ ] `DockerEnvironment` asli di DockWings — **butuh VPS**
- [ ] WebSocket console real-time — **butuh VPS**
- [ ] File manager (proxy ke SFTP Wings) — **butuh VPS**
- [ ] Testing `WingsService` ↔ DockWings end-to-end — **butuh VPS**
- [ ] Billing/expiry integration (opsional, buat dipakai di Ahmad Store)

## Kontribusi

Mau bantu development DockPanel? Cek [CONTRIBUTING.md](CONTRIBUTING.md).

## License

MIT
