# Tasks

## Active

- [ ] **Resource Filament "Pengguna"** - lihat saja, plus aksi "Reset kata sandi" yang mencabut semua token

## Waiting On

- [ ] **Kontrak API (`docs/kontrak-api.md`)** - dari pemilik, since 2026-10-09; semua butir di Backlog v0.1 menunggu file ini
- [ ] **Email admin yang benar** - dari pemilik, since 2026-10-09; `iqbal@it@tangcity.com` punya dua tanda `@` sehingga tidak bisa dipakai masuk
- [ ] **Keputusan: `personal_access_tokens.id` ikut ULID atau tetap bigint** - dari pemilik, since 2026-10-09
- [ ] **Konfirmasi: migrasi bawaan belum pernah jalan di server** - dari pemilik, since 2026-10-09; migrasinya diedit langsung
- [ ] **Merge `feat/fondasi-proyek` ke `main`** - oleh pemilik, setelah `php artisan test --compact` hijau
- [ ] **Kredensial Cloudflare R2 dan Firebase** - dari pemilik; disk `r2` dan FCM belum pernah dicoba ke layanan asli

## Backlog v0.1

- [ ] **Penanganan error terpusat** - bentuk JSON `message`, `code`, `errors`, `current` di `bootstrap/app.php`; menunggu kontrak
- [ ] **Akun dan profil** - daftar, masuk, `/me`, foto profil; menunggu kontrak
- [ ] **Perangkat** - token FCM, satu token Sanctum per HP; menunggu kontrak
- [ ] **Ruang Pribadi dan Ruang Bersama** - menunggu kontrak
- [ ] **Kode undangan** - buat, terima, cabut; menunggu kontrak
- [ ] **Daftar tugas dan tugas** - penanggung jawab dan pengingat; menunggu kontrak
- [ ] **Tabungan** - target, setoran, dan `config/currencies.php`; menunggu kontrak
- [ ] **Sync (`GET /sync`)** - menunggu kontrak
- [ ] **Push notification** - job FCM dan pengingat terjadwal; menunggu kontrak
- [ ] **Resource Filament lain** - Ruang, Undangan, Token perangkat; menunggu model dari kontrak
- [ ] **`DemoSeeder`** - dua akun contoh dan satu Ruang Bersama; menunggu model dari kontrak

## Someday

- [ ] **v0.2** - perjalanan, checklist persiapan, tanggal penting
- [ ] **v0.3** - kenangan dan foto
- [ ] **v1.0** - ekspor data, hapus akun, tugas berulang
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
