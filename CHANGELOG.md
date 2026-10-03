# Changelog

Semua perubahan penting di project ini dicatat di sini.

## [0.14.0] - 2026-10-03
> "Rilis keamanan: header, route storage, dan rate limit reset password." 🐧

### Added

- Middleware `SecurityHeaders` yang dipasang ke semua respons: `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` (kamera, mikrofon, lokasi dimatikan), dan `Strict-Transport-Security` (30 hari, hanya di HTTPS).

### Changed

- Route bawaan Laravel `storage/{path}` dimatikan (`'serve' => false` di disk lokal) karena DockPanel nggak memakainya.
- `forgot-password` dan `reset-password` dibatasi 5 permintaan per menit (login dan 2FA sudah punya rate limit sendiri di controller).
- Versi panel jadi 0.14.0. Tetap butuh DockWings v0.2.0+.

### Catatan

- Setelan production yang disarankan di `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=warning`, `SESSION_SECURE_COOKIE=true`, dan izin file `640` (bukan world-readable). Dengan `APP_DEBUG=true`, halaman error menampilkan stack trace, path server, dan query SQL.
- HSTS sengaja dimulai 30 hari. Naikkan setelah sertifikat HTTPS terbukti stabil.
- Belum ada Content-Security-Policy karena banyak halaman memakai `<script>` dan `style` inline.

## [0.13.0] - 2026-10-03
> "Halaman Server sekarang bertab, kayak Pterodactyl." 🐧

### Added

- Halaman detail Server satu halaman bertab: About, Details, Build Configuration, Startup, Database, Mounts, Subusers, Manage, dan Delete. Tab terakhir yang dibuka diingat selama sesi, dan tombol kecil di ujung tab buka server sebagai user.
- Tab About: tabel Information (ID, UUID, egg, CPU, memory, swap, disk, Block IO, koneksi utama, image, masa aktif) plus kartu Owner dan Node di kanan.
- Tab Build Configuration: form resource (CPU, memory, swap, disk, Block IO) dan pengaturan allocation dipisah jadi dua box.
- Tab Manage: kartu Suspend/Unsuspend dan Provision ke Wings. Reinstall, Install Status, dan Transfer tampil nonaktif dengan label "Belum tersedia".

### Changed

- `ServerController@show` sekarang merender view yang sama dengan `edit` (semua data tab dimuat sekali), jadi redirect dari form lain tetap mendarat di halaman yang benar.
- Validasi `ServerController@update` pakai `sometimes|required`, supaya form Details (nama, owner, deskripsi) dan form Build Configuration (resource) bisa disimpan terpisah.
- `DashboardController` nggak lagi menghitung statistik yang sudah nggak ditampilkan di Overview sejak 0.12.0, jadi lebih sedikit query tiap buka Overview.

### Fixed

- `ParseError` di halaman detail Server akibat `@if` inline yang menempel ke teks di kartu Suspend.

### Catatan

- Command startup dan Docker image tampil read-only; belum ada route buat mengubahnya.
- `resources/views/servers/edit.blade.php` nggak dipakai lagi, sengaja nggak dihapus.
- Form masa aktif (`servers.expiry.update`) belum punya tampilan di halaman Server.
- Belum ada: Reinstall, Install Status, Transfer, Force Delete, CPU pinning, OOM Killer, limit fitur aplikasi, dan External Identifier.

## [0.12.0] - 2026-10-03
> "Overview, Servers, dan Users ikut berubah jadi satu bahasa sama halaman Node." 🐧

### Changed

- Overview: sekarang berisi box System Information (versi panel) dan empat tombol (Report Issue, Changelog, GitHub, DockWings). Kotak statistik, tabel node, server terbaru, dan aktivitas terbaru dihapus dari tampilan.
- Servers: daftar jadi tabel gaya Pterodactyl dengan kolom Server Name, UUID, Owner, Node, Connection (IP:port dari primary allocation), badge status, dan tombol manage. Search, filter status, dan filter node pindah ke header box. Pagination tetap ada.
- Users: daftar jadi tabel dengan kolom ID, Email (bintang buat admin), Name, 2FA, Servers Owned (link ke daftar server milik user itu), dan Can Access (jumlah server user lain yang bisa diakses sebagai subuser). Search jalan di sisi browser.
- `ServerController@index` ikut eager-load `primaryAllocation`, dan `UserController@index` ikut menghitung `subuserOfServers`.
- Versi panel jadi 0.12.0. Tetap butuh DockWings v0.2.0+.

### Catatan

- Kolom "Client Name" dan "Username" ala Pterodactyl digabung jadi satu kolom Name, karena model User cuma punya `name`.

## [0.11.0] - 2026-10-03
> "Halaman Node sekarang kayak Pterodactyl beneran." 🐧

### Added

- Daftar Node gaya Pterodactyl: kolom Name, Location, Memory, Disk, Servers, SSL, Public, kotak search, dan tombol Create New.
- Ikon status daemon (hati) di daftar Node, dicek lewat AJAX per node (`GET /nodes/{node}/status`) supaya halaman nggak hang kalau Wings mati.
- Halaman detail Node dengan tab About / Settings / Configuration / Allocation, box Information (versi daemon, OS/arsitektur, jumlah CPU thread), dan box Delete Node.
- Kotak At-a-Glance: Disk Space Allocated, Memory Allocated, dan Total Servers. Warnanya berubah sesuai persentase alokasi.

### Changed

- Info FQDN, port SFTP, publik, dan maintenance dipindah dari halaman detail ke tab Settings.
- Versi panel jadi 0.11.0. Tetap butuh DockWings v0.2.0+.

### Catatan

- Belum ada: tab Servers, versi kernel, dan "(Latest: …)" di Daemon Version.

## [0.10.0] - 2026-10-03

> "Tampilan login, admin, dan client sekarang satu bahasa, mirip Pterodactyl." 🐧

### Added
- Halaman login gaya Pterodactyl (judul + kartu "Login to Continue", label mengambang, tombol tampilkan password, checkbox "Ingat saya"). Halaman 2FA ikut disamain lewat layout baru `layouts/auth`.
- Batas percobaan login: maksimal 5 kali per menit per kombinasi email + IP, berlaku juga buat kode 2FA.
- Admin panel: header atas dengan ikon, sidebar bisa di-collapse (pilihan disimpan di browser), kartu bergaya box AdminLTE, dan dashboard dengan kotak statistik berwarna + System Information.
- Client area: navbar atas ala Pterodactyl, daftar server di `/client` (route `client.index`) lengkap dengan alamat IP:port dan kotak pencarian, tab bar dan tombol power berwarna di halaman server.
- Admin bisa lihat server miliknya, milik user lain, atau semua lewat filter di daftar server, dan punya ikon pintas ke panel admin di navbar client.
- Stylesheet baru `public/css/dp-ptero.css` (dimuat setelah `dp-theme.css`).

### Fixed
- Ikon sidebar/topbar (`home`, `settings`, `api`, `database`, `location`, `mounts`, `users`, `user`, `menu`, `logout`) sebelumnya belum ada di `partials/icon`, jadi tampil kosong. Sekarang sudah ditambahin.
- Checkbox "Ingat saya" sebelumnya dibaca controller tapi nggak ada di form login.

### Changed
- Versi panel jadi 0.10.0. Tetap butuh DockWings v0.2.0+.

## [0.9.0] - 2026-10-02

> "Panel sama Wings akhirnya ngobrol pakai bahasa yang sama, dan halaman server mulai kayak Pterodactyl." 🐧

### Added
- Halaman server (client area) dirombak ala Pterodactyl: judul + breadcrumb, pill status server, kode UUID dan alamat, tombol power di kanan, lalu tile CPU / Memory / Disk. Tab dan console tetap, console lebih tinggi.
- Halaman detail Node nampilin status daemon dan versi DockWings (`GET /api/system`), mirip halaman node Pterodactyl. Timeout 3 detik biar halaman nggak hang kalau Wings mati.
- Pesan error koneksi ke Wings sekarang ngasih petunjuk (scheme https/http nggak cocok, koneksi ditolak, timeout), dipakai di Provision dan halaman Node.

### Fixed
- `cURL error 35 ... wrong version number` pas Provision ke Wings: penyebabnya scheme Node `https` padahal Wings melayani HTTP. Sekarang ada petunjuk jelasnya; solusinya set scheme Node ke `http`, atau aktifin `ssl` di config Wings v0.2.0+.

### Changed
- Versi panel jadi 0.9.0. Butuh DockWings v0.2.0+ buat tampilan versi daemon di halaman Node.

## [Sebelumnya, belum dirilis] - 2026-09-28

### Added
- Halaman detail server di client area (`/client/servers/{server}`) dengan tab Console, Files, Settings, Startup.
- Tombol power (start/restart/stop/kill) dan kirim command dari client area, dengan akses owner, subuser, atau root admin.
- Resource bar CPU/Memory/Disk (polling 5 detik) lewat `ServerResourceService`; fallback ke mode mock kalau Wings belum aktif.
- Masa aktif server: kolom `expires_at`, `suspension_reason`, `expiry_notified_at`.
- Command `servers:suspend-expired` (auto-suspend) dan `servers:notify-expiring` (email pengingat H-3), dijadwalkan lewat scheduler.
- Form admin buat set tanggal expired atau perpanjang X hari; suspend karena expired otomatis dibuka saat diperpanjang.
- Test baru: `ClientServerTest`, `ServerExpiryTest`, dan helper `tests/Concerns/MakesServers`.

### Fixed
- `ParseError` di `eggs/edit` dan `servers/edit` akibat `'{{'` di dalam echo Blade.

### Security
- `database/*.sqlite` dan `.phpunit.result.cache` masuk `.gitignore`.

## [0.8.1] - 2026-07-21

> "Sekecil apapun, versi panel sekarang keliatan di layar, bukan cuma di CHANGELOG." 🐧

### ✨ Ditambahkan
- Halaman Overview sekarang nampilin versi panel di card "System Information", persis pola Pterodactyl (`You are running DockPanel version 0.8.0.`)
- Versi panel dipusatin di `config/app.php` (`config('app.version')`) — dipakai bareng di Overview dan footer, jadi tiap rilis baru cukup update satu tempat aja

---

## [0.8.0] - 2026-07-17

> "Sekarang instalnya segampang Pterodactyl, dan tampilannya nggak keliatan proyek dari HP lagi." 🐧

### ✨ Ditambahkan
- **`install.sh`** — installer satu perintah mirip `pterodactyl-installer`, bisa dijalanin langsung di VPS Ubuntu 24.04:
  ```
  bash <(curl -s https://raw.githubusercontent.com/Julakk/DockPanel/main/install.sh)
  ```
  Ada 2 pilihan: install Panel (PHP, Composer, MariaDB, Nginx, opsional SSL Certbot) atau install Node/Wings (Docker, Go, systemd service)
- **UI/UX refresh total** — palet warna baru pakai CSS custom properties (`:root` variables) biar konsisten, tipografi native font stack, card dengan border+shadow, hover/focus state di semua button/input/table row, topbar sticky dengan efek blur
- Halaman auth (login, forgot password, reset password, 2FA challenge) disamain temanya, dulu masih pakai styling lama yang beda sendiri

### 🐛 Diperbaiki
- Badge versi di footer yang kepasang hardcode `v0.4.0` dari lama, sekarang ngikutin versi rilis terbaru (`v0.8.0`)
- Development lokal disaranin pindah ke SQLite (`DB_CONNECTION=sqlite`) biar nggak perlu nyalain MariaDB manual tiap buka Termux — perbaikan workflow, bukan perubahan kode

### 📝 Catatan
- Installer script ditulis manual tanpa bisa ditest langsung di VPS Ubuntu beneran (belum ada VPS) — logic-nya ngikutin cara setup standar PHP/Nginx/MariaDB/Docker/Go, tapi kemungkinan ada penyesuaian kecil pas dicoba pertama kali di VPS asli
- SQLite cuma buat convenience development, produksi/VPS installer tetap pakai MariaDB (nggak berubah)

---

## [0.7.0] - 2026-07-16

> "Subuser, activity log, lupa password, 2FA beneran — empat kado sekaligus." 🐧

### ✨ Ditambahkan
- **Subusers** — admin bisa kasih akses server ke user lain tanpa jadiin mereka owner, lengkap dengan pilihan permission granular (`control.start`, `console.access`, `files.read`, dll) dari halaman edit server
- **Activity Log** — histori aktivitas otomatis kecatet buat login sukses/gagal, logout, ganti password/email, enable/disable 2FA; bisa dilihat di Account > Activity dengan waktu relatif ("2 jam lalu")
- **Forgot Password** — flow reset password lengkap: form "Lupa password?" di login → link reset (lewat mail driver `log` buat development) → form set password baru
- **Two-Factor Authentication beneran** — implementasi TOTP (RFC 6238) ditulis manual pakai `hash_hmac`, nggak nambah dependency composer. Compatible sama Google Authenticator/Authy. Login sekarang ada tahap tambahan challenge kode 6 digit kalau 2FA aktif
- Unit test khusus buat algoritma TOTP (`TwoFactorServiceTest`) pakai implementasi referensi independen buat cross-check

### 🐛 Diperbaiki
- `two_factor_secret` dan `two_factor_enabled_at` ternyata nggak ada di `$fillable` model `User`, bikin data 2FA diam-diam nggak kesimpen (mass assignment ke-block) — ketauan dari test yang gagal, bukan dari production
- Format kode sesuai standar Pint (`ServerController.php`, `User.php`, `TwoFactorService.php`, `AuthFeaturesTest.php`)
- `config/mail.php` yang ternyata belum ada dari awal skeleton — dibutuhin buat fitur forgot password

### 📝 Catatan
- 44 test lolos semua — mayoritas fitur besar DockPanel yang bisa dikerjain tanpa VPS sekarang udah kelar
- Sisa roadmap besar (integrasi Docker asli, WebSocket console, file manager SFTP) tetap nunggu VPS tersedia

---

## [0.6.0] - 2026-07-15

> "User biasa akhirnya punya rumah sendiri, nggak numpang di sidebar admin lagi." 🐧

### ✨ Ditambahkan
- **Client Area** — sidebar & dashboard terpisah buat user biasa (non-admin):
  - **My Servers** — daftar server milik sendiri (owner atau subuser), card + resource bar placeholder, tanpa tombol admin
  - **Account Settings** — update password (validasi password lama), update email (validasi password), placeholder Two-Step Verification
  - **API Credentials** — token API personal, bisa dipakai semua user (beda dari "Application API" yang tetap khusus admin)
- Sidebar sekarang otomatis nyesuain isi berdasarkan role: admin lihat semua menu, user biasa cuma lihat "My Servers" + section "Account"
- `DashboardController` cabang otomatis: admin lihat Administrative Overview, user biasa lihat My Servers
- Test coverage lengkap (5 test) buat area Client

### 🐛 Diperbaiki
- Route `account.edit` dan turunannya sempat ilang gara-gara ketiban merge dari commit lain — ke-detect dari test yang gagal massal (`Route [account.edit] not defined`), diperbaiki dengan restore isi `routes/web.php` yang lengkap
- Format kode sesuai standar Pint (`AccountController.php`, `ClientAreaTest.php`)

### 📝 Catatan
- Semua fitur di atas 100% bisa dites tanpa VPS
- Reminder: kalau `git push` ketolak "fetch first" setelah `git pull`, selalu cek ulang file kunci (`routes/web.php`, dll) pakai `grep` sebelum lanjut, biar nggak ketiban regresi diam-diam kayak kejadian ini
- `start.sh` kadang false-alarm gagal nyalain MySQL padahal cuma butuh waktu lebih lama buat startup — kalau muncul "Gagal nyalain MySQL", coba `mysqladmin -u root status` manual dulu sebelum panik

---

## [0.5.0] - 2026-07-13

> "Sidebar-nya nggak ada yang nunjuk ke halaman kosong lagi." 🐧

### ✨ Ditambahkan
- **Users** — CRUD user lengkap (admin bisa bikin/edit/hapus user, toggle role admin, proteksi nggak bisa hapus akun sendiri)
- **Locations** — kategorisasi Node berdasarkan lokasi fisik (short code + deskripsi), Node sekarang bisa di-assign ke Location
- **Settings** — halaman konfigurasi panel (company name, requirement 2FA, default language)
- **Application API** — manajemen token API pakai Laravel Sanctum (generate, lihat daftar, cabut token)
- **Databases** — manajemen database host (buat di-assign ke server nantinya), password di-encrypt otomatis
- **Mounts** — manajemen mount point tambahan buat server, bisa di-assign ke banyak Node sekaligus
- Semua link sidebar yang sebelumnya placeholder (`href="#"`) sekarang nyambung ke halaman asli
- Test coverage buat semua fitur baru di atas
- **Assign Database Host & Mount ke Server** — provisioning database per-server (nama dinamespace pakai uuid_short biar nggak bentrok), checklist assign mount ke server

### 🐛 Diperbaiki
- Migration `personal_access_tokens` yang ternyata kelewat dari awal skeleton — tanpa ini, fitur apapun yang pakai Sanctum token bakal error
- Format kode sesuai standar Pint (`routes/web.php`, `AdminPagesTest.php`, `ServerController.php`)

### 📝 Catatan
- Semua fitur baru di atas 100% bisa dites tanpa VPS
- Reminder development: MySQL/MariaDB **wajib dinyalain manual** tiap buka Termux baru (`bash start.sh`) — Termux nggak punya auto-start service

---

## [0.4.0] - 2026-07-11

> "Sekarang mukanya beneran mirip panel hosting beneran." 🐧

### ✨ Ditambahkan
- **Redesign total layout** — sidebar navigasi ala Pterodactyl (section Basic Administration / Management / Service Management), menggantikan navbar horizontal
- Sidebar responsive — collapse jadi hamburger menu di layar sempit/HP
- Dashboard baru: "Administrative Overview" dengan card jumlah Node/Server/Egg
- Server list sekarang tampil sebagai card dengan resource bar CPU/Memory/Disk (placeholder abu-abu, nunggu Wings aktif buat data beneran)
- Semua emoji di UI diganti jadi inline SVG icon solid/filled style (logo, server, globe, egg, package, plug, sparkle, home, settings, api, database, location, users, mounts, menu, logout) — satu partial reusable `partials/icon.blade.php`
- Status badge konsisten (pill + titik warna) buat semua status: running/installing/offline/suspended, dll
- Empty state yang lebih jelas (ikon + pesan + CTA) di semua halaman index yang datanya kosong
- Breadcrumb navigasi di semua halaman create/edit/show, format seragam "Admin > X"
- Footer ala Pterodactyl — copyright, versi panel, waktu render halaman

### 🐛 Diperbaiki
- Mobile viewport meta tag yang kelewat dari awal, bikin render Chrome mobile nggak proporsional

### 📝 Catatan
- Resource usage bar di server list masih dummy/kosong sampai Wings beneran aktif dan bisa lapor data CPU/RAM/Disk real-time

---

## [0.3.1] - 2026-07-11

> "Akhirnya nyala di browser beneran, bukan cuma centang hijau di CI." 🐧

### 🐛 Diperbaiki
- Fix `ParseError` di halaman Egg (`create.blade.php` & `edit.blade.php`) — literal `{{VAR_NAME}}` di teks bantuan salah escape, bikin Blade compiler bingung. Diganti pakai sintaks `@{{ }}` yang benar.
- Default `.env.example` diganti ke driver `file`/`sync` (bukan `redis`) — biar development lokal di Termux nggak wajib nyalain Redis server dulu.

### 📝 Catatan
- Panel pertama kali berhasil dites langsung di browser (`php artisan serve` dari Termux) — login, dashboard, navbar, semua fitur CRUD sejauh ini kekonfirmasi jalan
- Sempat ada kendala environment: folder kerja pindah dari `~/storage/downloads/DockPanel` ke `~/DockPanel` (home Termux) buat hindari masalah permission shared storage yang berulang kali muncul
- Repo Wings direncanakan dipisah jadi repo sendiri (`Julakk/DockWings`) nanti pas ada VPS — konsisten sama pola Pterodactyl asli (Panel dan Wings repo terpisah)

---

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
- [x] Testing manual lanjutan (bikin node/nest/egg/server beneran dari browser)
- [x] Repo `DockWings` terpisah — skeleton awal udah jalan (Go, routing, auth middleware, interface Docker stub)
- [x] UI polish — sidebar navigasi, status badge, empty state, breadcrumb, icon SVG
- [x] Halaman fungsional buat menu sidebar (Users, Locations, Settings, Application API, Databases, Mounts)
- [x] Assign Database Host & Mount ke Server
- [x] Client Area — dashboard, account settings, API credentials buat user biasa (non-admin)
- [x] Subusers — kasih akses server ke user lain dengan permission granular
- [x] Activity Log — histori aktivitas per user
- [x] Forgot Password — flow reset password lengkap
- [x] Two-Factor Authentication beneran (TOTP, RFC 6238)
- [ ] `DockerEnvironment` asli di DockWings — butuh VPS
- [ ] WebSocket console real-time — butuh VPS
- [ ] File manager (proxy SFTP) — butuh VPS
- [ ] Testing `WingsService` (Panel) ↔ DockWings end-to-end — butuh VPS


