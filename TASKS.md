# Tasks

## Active

- [ ] **Target tabungan** - `PUT`, `PATCH`, `DELETE /savings-goals/{id}`

## Waiting On

- [ ] **Teks push notification** - dari pemilik, since 2026-10-10; kontrak merujuk bagian "Bahasa dan microcopy" di tab utama, yang belum ada di repo
- [ ] **Persetujuan daftar "Perubahan dari rancangan awal" di kontrak** - dari pemilik, since 2026-10-10; semua butirnya belum dicentang, kode mengikuti kontrak apa adanya
- [ ] **Konfirmasi: migrasi bawaan belum pernah jalan di server** - dari pemilik, since 2026-10-09; migrasinya diedit langsung
- [ ] **Merge `feat/fondasi-proyek` lalu `feat/api-v0.1` ke `main`** - oleh pemilik, setelah `php artisan test --compact` hijau
- [ ] **Kredensial Cloudflare R2 dan Firebase** - dari pemilik; disk `r2` dan FCM belum pernah dicoba ke layanan asli

## Backlog v0.1

- [ ] **Setoran** - `PUT`, `PATCH`, `DELETE /savings-entries/{id}`; status tercapai
- [ ] **Sync** - `GET /sync` dengan kursor per koleksi
- [ ] **Push notification** - job FCM untuk enam jenis push
- [ ] **Pengingat terjadwal** - scheduler tiap menit, klaim tanpa menyentuh `updated_at`
- [ ] **Resource Filament lain** - Ruang, Undangan, Token perangkat
- [ ] **`DemoSeeder`** - dua akun contoh dan satu Ruang Bersama

## Someday

- [ ] **v0.2** - perjalanan, checklist persiapan, tanggal penting
- [ ] **v0.3** - kenangan dan foto
- [ ] **v1.0** - ekspor data, hapus akun, tugas berulang, lupa kata sandi di app, keluar dari Ruang Bersama
- [ ] **v1.x** - penarikan tabungan, realtime

## Done

- [x] ~~Dokumentasi proyek masuk ke CLAUDE.md~~ (2026-10-09)
- [x] ~~Pasang Sanctum (`install:api`) dan Filament 5 beserta panel `/admin`~~ (2026-10-09)
- [x] ~~Pakai MySQL untuk lokal dan pengujian~~ (2026-10-09)
- [x] ~~ULID dan timestamp mikrodetik di tabel bawaan~~ (2026-10-09)
- [x] ~~Batasi panel admin ke `ADMIN_EMAIL`~~ (2026-10-09)
- [x] ~~Baca zona waktu dari `APP_TIMEZONE`~~ (2026-10-09)
- [x] ~~Pasang adaptor S3 dan disk `r2`~~ (2026-10-09)
- [x] ~~Pasang `kreait/laravel-firebase`~~ (2026-10-09)
- [x] ~~Pasang Intervention Image~~ (2026-10-09)
- [x] ~~Pasang Scramble di `/docs/api`~~ (2026-10-09)
- [x] ~~Pasang `laravel-lang` dan terjemahan `lang/id`~~ (2026-10-09)
- [x] ~~Route API di bawah prefix `/api/v1`~~ (2026-10-09)
- [x] ~~Seeder user admin (`AdminUserSeeder`): email dari `ADMIN_EMAIL`, kata sandi `password`, hanya lokal~~ (2026-10-09)
- [x] ~~Resource Filament "Pengguna": daftar dan lihat, plus aksi "Reset kata sandi" yang mencabut semua token~~ (2026-10-09)
- [x] ~~User admin lokal `iqbal.it@tangcity.com` dibuat lewat `AdminUserSeeder`~~ (2026-10-09)
- [x] ~~Kontrak API masuk ke `docs/kontrak-api.md`~~ (2026-10-10)
- [x] ~~Keputusan: `personal_access_tokens.id` tetap bigint, sesuai contoh token di kontrak~~ (2026-10-10)
- [x] ~~Skema dan model data v0.1: ruang, anggota, undangan, daftar, tugas, target, setoran, token perangkat, kolom profil~~ (2026-10-10)
- [x] ~~Penanganan error terpusat: `message`, `code`, `errors`, `current`, dan batas request per endpoint~~ (2026-10-10)
- [x] ~~Akun: `POST /auth/register`, `/auth/login`, `/auth/logout`; Ruang Pribadi dan daftar "Tugas" dibuat saat daftar~~ (2026-10-10)
- [x] ~~Profil: `GET /me`, `PATCH /me`~~ (2026-10-10)
- [x] ~~Foto profil: `POST /me/avatar`, `DELETE /me/avatar`; disimpan ulang sebagai JPEG 512 x 512 tanpa EXIF~~ (2026-10-10)
- [x] ~~Perangkat: `POST /devices`, `DELETE /devices/{token}`~~ (2026-10-10)
- [x] ~~Ruang: `GET /spaces` beserta profil anggotanya~~ (2026-10-10)
- [x] ~~Kode undangan: `POST /invitations`, `POST /invitations/accept`; Ruang Bersama dibuat saat kode diterima~~ (2026-10-10)
- [x] ~~Daftar tugas: `PUT`, `PATCH`, `DELETE /lists/{id}`; PUT aman diulang, daftar aktif terakhir tidak bisa dihapus atau diarsipkan~~ (2026-10-10)
- [x] ~~Tugas: `PUT`, `PATCH`, `DELETE /tasks/{id}`; penanggung jawab, `remind_at`, selesai, pindah daftar, push `task_assigned` dan `task_completed`~~ (2026-10-10)
