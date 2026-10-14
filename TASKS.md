# Tasks

## Active

## Waiting On

- [ ] **Cek perbaikan IP klien di server** - menunggu `fix/ip-klien-asli` ter-deploy, since 2026-10-14; tiga login gagal dengan email yang sama, dua dengan `X-Forwarded-For` palsu, harus memberi `X-RateLimit-Remaining` 4, 3, 2
- [ ] **Samakan dokumen perencanaan di claude.ai dengan salinan kontrak** - oleh pemilik, since 2026-10-11; salinan di repo sudah dicentang, dua pertanyaan terbukanya dijawab, dan dua aturan sync ditambahkan, sedangkan dokumen aslinya belum
- [ ] **Coba pulihkan satu backup** - oleh pemilik, since 2026-10-11; pulihkan ke database sementara di Coolify untuk memastikan backup-nya benar-benar bisa dipakai
- [ ] **Uji push ke HP sungguhan** - menunggu app Flutter, since 2026-10-11; daftarkan perangkat lewat `POST /devices`, lalu picu satu push dan pastikan notifikasinya muncul

## Backlog v0.1

## Someday

- [ ] **v0.2** - perjalanan, checklist persiapan, tanggal penting
- [ ] **v0.3** - kenangan dan foto
- [ ] **v1.0** - ekspor data, hapus akun, tugas berulang, lupa kata sandi di app, keluar dari Ruang Bersama
- [ ] **v1.x** - penarikan tabungan, realtime
- [ ] **Sebelum rilis publik** - jalankan proses di container sebagai user non-root, dan pertimbangkan mode worker FrankenPHP lewat Laravel Octane

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
- [x] ~~Target tabungan: `PUT`, `PATCH`, `DELETE /savings-goals/{id}`; mata uang hanya saat dibuat, arsip, hapus berantai ke setoran~~ (2026-10-10)
- [x] ~~Setoran: `PUT`, `PATCH`, `DELETE /savings-entries/{id}`; status tercapai dihitung ulang, push `savings_deposit` dan `savings_achieved` (sekali per target)~~ (2026-10-10)
- [x] ~~Sync: `GET /sync` dengan `since` dan `cursor`; 500 baris per koleksi per halaman, urut `(updated_at, id)`, mundur 5 detik, isi Ruang Pribadi pasangan tidak ikut~~ (2026-10-10)
- [x] ~~Pengingat terjadwal: `php artisan reminders:send` tiap menit dengan `withoutOverlapping()`; klaim tanpa menyentuh `updated_at`, telat lebih dari 30 menit ditandai terkirim tanpa push~~ (2026-10-10)
- [x] ~~Push notification: job FCM untuk keenam jenis push (`task_reminder`, `task_assigned`, `task_completed`, `savings_deposit`, `savings_achieved`, `partner_joined`); teksnya masih sementara~~ (2026-10-10)
- [x] ~~Resource Filament: Ruang dan anggotanya (lihat saja), Undangan (lihat, aksi "Cabut"), Token perangkat (lihat, hapus); isi tugas, daftar, dan tabungan tidak ditampilkan~~ (2026-10-10)
- [x] ~~`DemoSeeder`: akun `aku@contoh.test` dan `pasangan@contoh.test` (kata sandi `password`), Ruang Bersama lewat alur undangan, beberapa daftar, tugas, target, dan setoran; ikut dipanggil `DatabaseSeeder`~~ (2026-10-10)
- [x] ~~Persiapan produksi: aplikasi memercayai proxy Coolify, syarat PHP disamakan dengan `composer.lock` (8.4), dan image FrankenPHP yang menjalankan web, antrean, dan scheduler~~ (2026-10-11)
- [x] ~~Panel admin: halaman utama disebut "Dashboard", dan ikon menu Ruang dibedakan dari ikon Dashboard~~ (2026-10-11)
- [x] ~~Migrasi bawaan yang diedit: database produksi dibuat baru dan seluruh migrasi jalan bersih di sana~~ (2026-10-11)
- [x] ~~Aplikasi di Coolify: MySQL 8.4, build pack Dockerfile, domain `todolist.iqbalfhz.my.id`, variabel lingkungan, dan auto deploy lewat webhook GitHub~~ (2026-10-11)
- [x] ~~`feat/api-v0.1` di-merge ke `main` dan ter-deploy; image FrankenPHP ter-build di percobaan pertama~~ (2026-10-11)
- [x] ~~Akun admin dibuat di server dan bisa masuk ke `/admin`~~ (2026-10-11)
- [x] ~~Keputusan pemilik atas kontrak: ke-18 butir "Perubahan dari rancangan awal" disetujui, nominal tetap disebut di push setoran, dan lupa kata sandi cukup lewat reset manual oleh admin~~ (2026-10-11)
- [x] ~~Teks push: teks yang sudah ada di `lang/id/push.php` disahkan pemilik sebagai teks final~~ (2026-10-11)
- [x] ~~Uji alur nyata di server dengan dua akun uji: daftar, undangan, daftar tugas, tugas, tabungan, sync, keluar, dan masuk; ke-28 pemeriksaan lolos~~ (2026-10-11)
- [x] ~~Akun uji dihapus dari server; pemeriksaannya menunjukkan pengingat terjadwal mengklaim tugas yang jatuh tempo, antrean kosong, dan tidak ada job gagal~~ (2026-10-11)
- [x] ~~Cloudflare R2 terpasang di server (bucket `todolist-avatar`); unggah, ganti, dan hapus foto profil lolos uji di server~~ (2026-10-11)
- [x] ~~Kegagalan penyimpanan ke R2 sekarang tercatat penyebabnya di log~~ (2026-10-11)
- [x] ~~Firebase: proyek `todolist-cb119` dibuat, kredensialnya dipasang di server sebagai File Mount, dan FCM menerimanya (uji validasi token)~~ (2026-10-11)
- [x] ~~Akun uji foto dihapus dari server~~ (2026-10-11)
- [x] ~~Coolify tidak lagi mengirim variabel ke proses build (Build arguments: Managed manually in Dockerfile)~~ (2026-10-11)
- [x] ~~Backup database produksi: jadwal harian di Coolify, 7 backup lokal terakhir disimpan, dan backup pertama berhasil~~ (2026-10-11)
- [x] ~~Backup database juga diunggah ke R2 (bucket `todolist-backup`, 30 backup terakhir disimpan); backup pertama tersedia di server dan di R2~~ (2026-10-11)
- [x] ~~Salinan kontrak di repo dicentang (18 butir), dua pertanyaan terbukanya dicatat sebagai keputusan, dan dua aturan sync ditambahkan~~ (2026-10-11)
- [x] ~~Nama aplikasi jadi Denly Days di repo: `.env.example` dan CLAUDE.md~~ (2026-10-14)
- [x] ~~Domain pindah ke `denlydays.iqbalfhz.my.id` lewat route Cloudflare Tunnel, `APP_NAME` dan `APP_URL` diganti di Coolify; `/up`, halaman masuk admin ("Denly Days"), dan API lolos dicek, dan `todolist.iqbalfhz.my.id` sudah tidak ada di DNS~~ (2026-10-14)
- [x] ~~IP klien di server: IP yang tercatat di tabel `sessions` sama dengan IP publik pemilik, jadi batas request tidak memakai IP Cloudflare~~ (2026-10-14)
- [x] ~~Batas request tidak bisa lagi diakali dengan `X-Forwarded-For` palsu: Laravel hanya memercayai pemanggil langsung (`trustProxies(at: 'REMOTE_ADDR')`), bukan semua IP~~ (2026-10-14)
