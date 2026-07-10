# Changelog

Semua perubahan penting di project ini dicatat di sini.

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
- [ ] CRUD Node (admin)
- [ ] CRUD Egg/Nest + import format JSON
- [ ] CRUD Server (admin + user-facing)
- [ ] WebSocket console real-time
- [ ] File manager (proxy SFTP)
- [ ] Testing `WingsService` ke daemon Wings asli (butuh VPS)
