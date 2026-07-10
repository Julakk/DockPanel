# Changelog

Semua perubahan penting di project ini dicatat di sini.

## [0.3.0] - 2026-07-10

> "Node, Nest, Egg — tiga elemen udah lengkap. Tinggal nyatuin jadi Server." 🐧

### ✨ Ditambahkan
- CRUD Nest (kategori game: Minecraft, SA-MP, FiveM, dll)
- CRUD Egg lengkap — docker image, startup command, install script
- Import Egg dari file JSON kompatibel format Pterodactyl
- Manage Variable per Egg (`ENV_VARIABLE` kayak `SERVER_JARFILE`, `WORLD_NAME`, dll)
- Navbar admin aktif penuh — Nodes, Nests, Eggs semua nyambung ke halaman asli

### 🐛 Diperbaiki
- Format kode sesuai standar Pint di test suite baru (`NestEggManagementTest`)

### 📝 Catatan
- Semua CRUD di atas 100% bisa dites tanpa VPS (murni Laravel + database)

---

## [0.2.0] - 2026-07-10

> "Belum ada VPS, tapi admin udah bisa ngatur node." 🐧

### ✨ Ditambahkan
- CRUD Node lengkap (admin-only): tambah/edit/hapus VPS, auto-generate daemon token
- Layout admin bareng (`layouts/app.blade.php`) — navbar konsisten dipakai semua halaman
- `UserFactory` buat kebutuhan testing

### 🐛 Diperbaiki
- Package `mockery/mockery` yang belum kedaftar di `composer.json`, bikin test gagal
- Format kode sesuai standar Pint (`UserFactory.php`)

---

## [0.1.0] - 2026-07-10

> "Belum ada Wings, tapi Panel-nya udah bisa napas." 🐧

### ✨ Ditambahkan
- Skeleton project Laravel 11 lengkap (`bootstrap`, `config`, `public/index.php`, `artisan`)
- Struktur database inti:
  - `users`, `nodes`, `nests`, `eggs` + `egg_variables`
  - `servers` + `server_variables` + `server_subusers`
  - `allocations`, `activity_logs`
- Model Eloquent lengkap untuk semua tabel di atas
- `WingsService` — service class buat komunikasi Panel ↔ daemon Wings (create server, power action, kirim command, JWT token WebSocket)
- `ServerPowerController` — contoh endpoint kontrol start/stop/restart/kill server
- Sistem **Auth**:
  - Login/logout dengan session guard
  - Middleware `root_admin` buat proteksi halaman admin
  - Dashboard sederhana pasca-login
  - Seeder akun admin pertama (`admin@ahmadstore.id`)
- CI otomatis via GitHub Actions:
  - Install dependency, generate key, migrate, code style check (Pint), test (PHPUnit)

### 🐛 Diperbaiki
- `composer.json`: longgarin constraint versi (`laravel/framework`, `firebase/php-jwt`) buat hindari security advisory range yang nge-block install
- File `artisan` yang sempat ketinggalan pas skeleton awal
- Base class `Controller.php` yang belum dibuat, bikin semua controller lain error
- `tests/Unit` folder placeholder biar PHPUnit nggak nyari folder kosong
- Format kode sesuai standar Pint di beberapa file (`Egg.php`, `User.php`, `WingsService.php`, dll)
- `composer.lock` dihapus dari repo — hindari lock version mismatch antara PHP lokal (Termux) dan PHP di CI

### 📝 Catatan
- **Wings (daemon Docker) belum diimplementasi** — masih pakai skeleton pemanggilan API di `WingsService`, path endpoint belum divalidasi ke daemon asli
- Development sepenuhnya dari HP via Termux, testing Wings/Docker butuh VPS terpisah (belum tersedia)

---

## Roadmap Selanjutnya
- [ ] CRUD Server (admin + user-facing)
- [ ] WebSocket console real-time
- [ ] File manager (proxy SFTP)
- [ ] Testing `WingsService` ke daemon Wings asli (butuh VPS)

