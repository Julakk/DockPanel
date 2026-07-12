# Contributing to DockPanel

Terima kasih atas ketertarikanmu untuk berkontribusi pada DockPanel! Kami sangat menghargai setiap bantuan, mulai dari pelaporan bug, perbaikan kode, hingga penambahan fitur.

## 💬 Komunikasi

Selain melalui GitHub, kamu bisa berdiskusi, bertanya, atau melaporkan kendala di server Discord resmi kami:

👉 [Join Discord DockPanel](https://discord.gg/N8j5TR3k6g)

## Cara Berkontribusi

1. **Fork Repositori** — Klik tombol "Fork" di pojok kanan atas halaman GitHub DockPanel.

2. **Clone Repositori** — Clone fork kamu ke environment lokal kamu.

   ```bash
   git clone https://github.com/USERNAME-KAMU/DockPanel.git
   cd DockPanel
   ```

3. **Bikin Branch Baru** — Jangan kerja langsung di `main`.

   ```bash
   git checkout -b fitur/nama-fitur-kamu
   # atau
   git checkout -b fix/nama-bug-yang-diperbaiki
   ```

4. **Install Dependency**

   ```bash
   composer install
   cp .env.example .env
   php artisan key:generate
   php artisan migrate --seed
   ```

5. **Kerjakan Perubahan** — Ikuti [Standar Kode](#-standar-kode) di bawah.

6. **Pastikan Test Lolos** sebelum push.

   ```bash
   vendor/bin/pint          # code style check, auto-fix kalau perlu
   php artisan test         # jalanin semua test
   ```

7. **Commit dengan Pesan yang Jelas**

   ```bash
   git add .
   git commit -m "feat: tambah fitur X"
   ```

8. **Push ke Fork Kamu**

   ```bash
   git push origin fitur/nama-fitur-kamu
   ```

9. **Buka Pull Request** — Dari halaman fork kamu di GitHub, klik "Compare & pull request" ke branch `main` DockPanel. Jelasin perubahan apa yang kamu bikin dan kenapa.

## 📐 Standar Kode

- Ikuti PSR-12 (dicek otomatis lewat Laravel Pint — jalanin `vendor/bin/pint` sebelum commit)
- Nama variabel & fungsi pakai bahasa Inggris, komentar boleh bahasa Indonesia
- Tulis test buat fitur baru, minimal happy-path + 1 edge case
- Migration baru harus reversible (isi method `down()`)

## 📝 Format Commit Message

Pakai prefix biar histori gampang dibaca:

- `feat:` — fitur baru
- `fix:` — perbaikan bug
- `docs:` — perubahan dokumentasi doang
- `refactor:` — perubahan kode tanpa ubah behavior
- `test:` — nambah/perbaiki test
- `chore:` — perubahan config, dependency, dll

Contoh: `feat: tambah CRUD subuser buat server`

## 🐛 Melaporkan Bug

Kalau nemu bug tapi belum sempat/nggak bisa fix sendiri, buka [issue baru](https://github.com/Julakk/DockPanel/issues) dengan info:

- Langkah buat reproduce bug-nya
- Behavior yang diharapkan vs yang beneran terjadi
- Screenshot kalau ada (terutama buat bug UI)
- Environment (PHP version, OS/Termux, browser)

## ✅ Checklist Sebelum Pull Request

- [ ] `vendor/bin/pint` udah dijalanin, nggak ada style issue
- [ ] `php artisan test` lolos semua
- [ ] Commit message jelas dan pakai prefix yang sesuai
- [ ] Nggak ada file `.env` atau credential yang ke-commit

Makasih udah mau bantu ngembangin DockPanel! 🐧
