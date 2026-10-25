# Tasks

## Active

- [ ] **Penarikan tabungan (v1.x)** - since 2026-10-26; butir 2 dari usulan sesi Flutter. Kontraknya disusun di dokumen perencanaan dulu, lalu dicentang pemilik. Sesudahnya: pin lokasi kenangan, reaksi dan komentar, lalu masuk dengan Google

## Waiting On

- [ ] **Uji push sinkron di dua HP** - oleh pemilik, since 2026-10-26; buka app di HP pasangan, ubah atau centang tugas di Ruang Bersama dari HP Anda, lalu pastikan HP pasangan ikut berubah dalam beberapa detik tanpa ditarik untuk menyegarkan
- [ ] **Bangun ulang app dengan Flutter 3.47.8** - oleh sesi Flutter, since 2026-10-24; Flutter 3.47.2 di laptop masih memercayai CA eMudhra yang bocor (GHSA-frr6-h895-5pxp, dart:io di Android), jadi koneksi HTTPS app bisa disadap. Perbaikannya ada di SDK: `flutter upgrade`, lalu build ulang dan pasang APK di kedua HP
- [ ] **Ganti `APP_KEY` produksi** - oleh pemilik, since 2026-10-24; kuncinya pernah ditempel ke percakapan saat menyiapkan Coolify. Dampaknya kecil, karena `APP_KEY` hanya dipakai untuk sesi admin (tidak ada kolom terenkripsi, dan token Sanctum disimpan sebagai hash). Di Terminal Coolify jalankan `php artisan key:generate --show`, isi `APP_PREVIOUS_KEYS` dengan kunci lama dan `APP_KEY` dengan kunci baru, lalu deploy ulang; sehari kemudian kosongkan `APP_PREVIOUS_KEYS`
- [ ] **Naikkan MySQL ke 8.4.13** - oleh pemilik, since 2026-10-24; tidak mendesak. Perbaikan keamanannya di sisi klien MySQL, sedangkan PHP memakai mysqlnd, dan database tidak dibuka ke internet. Kerjakan bersama perawatan berikutnya, setelah backup
- [ ] **Tugas berulang di app Flutter** - oleh sesi Flutter, since 2026-10-24; kontraknya di bagian "Tugas berulang (v1.0)" pada `docs/kontrak-api.md`: pilihan ulang di lembar tugas, `next_task` dari balasan PATCH, Urungkan yang mengirim `completed_at: null`, dan sync penuh sekali karena kolom baru di `tasks`
- [ ] **Uji unggah foto kenangan di server** - oleh pemilik, since 2026-10-23; setelah app punya v0.3: unggah beberapa foto ke kenangan di Ruang Bersama, pastikan foto tampil di HP pasangan dan push "menyimpan kenangan baru" datang sekitar 2 menit kemudian, lalu hapus satu foto
- [ ] **Uji ulang `savings_reminder` dari penjadwal** - oleh pemilik, since 2026-10-24; pada 24 Oktober tidak ada target yang punya jadwal setor di server, jadi tidak ada yang dikirim (penjadwal dan app sudah benar). Server mati pada 25 Oktober pukul 08.00, jadi uji untuk hari Minggu itu ikut terlewat. Atur jadwal mingguan untuk hari berikutnya di target Ruang Bersama, pastikan Detail target menampilkan jadwalnya, lalu tunggu pukul 08.00–08.30 WIB
- [ ] **Uji notifikasi setelah app ditutup di HP Xiaomi** - oleh pemilik, since 2026-10-23; kalau tertahan, app menambah tombol di Pengaturan > Notifikasi yang memunculkan pilihan Izinkan/Tolak dari Android (bukan panduan manual)
- [ ] **APK baru untuk HP pasangan** - oleh pemilik, since 2026-10-23; APK dengan fitur v0.3 sudah siap (24 Oktober), tinggal dipasang di HP pasangan
- [ ] **Coba pulihkan satu backup** - oleh pemilik, since 2026-10-11; pulihkan ke database sementara di Coolify untuk memastikan backup-nya benar-benar bisa dipakai

## Backlog v0.1

## Someday

- [ ] **v1.0** - ekspor data, hapus akun, tugas berulang, lupa kata sandi di app, keluar dari Ruang Bersama
- [ ] **v1.x** - penarikan tabungan, realtime
- [ ] **Sebelum rilis publik** - pasang lagi "Application restrictions" (Android apps) pada API key Android Firebase, yang dilepas ke None pada 22 Oktober 2026 karena membuat pendaftaran token FCM gagal (pembatasan per API tetap), jalankan proses di container sebagai user non-root, deploy tanpa jeda (sekarang setiap deploy membuat server membalas 502 sekitar 17 detik saat container diganti), dan pertimbangkan mode worker FrankenPHP lewat Laravel Octane

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
- [x] ~~Perbaikan IP klien ter-deploy dan dicek di server: tiga login gagal dengan email yang sama, dua dengan `X-Forwarded-For` palsu, memberi `X-RateLimit-Remaining` 4, 3, 2, dan link admin tetap HTTPS~~ (2026-10-14)
- [x] ~~Setelah perbaikan, IP di tabel `sessions` dicek ulang dan tetap IP publik pemilik~~ (2026-10-14)
- [x] ~~php2shell (CVE-2026-109618, CVE-2026-109619, RCE tanpa login, sudah dieksploitasi): image dikunci ke `dunglas/frankenphp:1.13.2-php8.4.27`, versi yang sudah ditambal; header bertitik seperti `X.Forwarded.For` sudah dicek tidak bisa memalsukan IP di server~~ (2026-10-22)
- [x] ~~Server memakai PHP 8.4.27 (`php -v` di Terminal Coolify), dan scheduler jalan di container baru (`reminders:send` tiap menit di log)~~ (2026-10-22)
- [x] ~~Dokumen perencanaan disamakan dengan salinan kontrak di repo (centang 18 butir, "Sudah diputuskan", dua aturan sync)~~ (2026-10-22)
- [x] ~~Tujuh keputusan Tanggal penting disetujui pemilik: ulang tahun anggota dari profil, jenis Lainnya, jadwal tetap H-7/H-1/hari H, tahun boleh kosong, ulang tahun hanya diingatkan ke pasangan, push `special_date_reminder` dengan preferensi `special_dates`, dan teks push~~ (2026-10-22)
- [x] ~~Kontrak Tanggal penting disetujui pemilik dan disalin ke `docs/kontrak-api.md`~~ (2026-10-22)
- [x] ~~Tanggal penting (v0.2) di API: `PUT`, `PATCH`, `DELETE /special-dates/{id}`, koleksi sync `special_dates`, `birth_date` anggota di sumber ruang, preferensi `special_dates`, dan pengingat `special-dates:remind` (H-7, H-1, hari H pukul 08.00)~~ (2026-10-22)
- [x] ~~Push sampai ke HP sungguhan: `task_completed` dan `savings_deposit` dalam uji kalian berdua, dan `partner_joined` uji dari server~~ (2026-10-22)
- [x] ~~Tanggal penting di app Flutter (repo mobile `da4046c`): layar Pengaturan > Tanggal penting, koleksi sync `special_dates` dengan sync penuh sekali, hitung mundur dan kartu ucapan di Beranda, `birth_date` anggota, switch `special_dates`, dan push yang membuka Beranda; uji lewat layar dan uji dua HP lulus terhadap API `e866b4e`~~ (2026-10-22)
- [x] ~~Kontrak Perjalanan dan checklist persiapan disetujui pemilik dan disalin ke `docs/kontrak-api.md`~~ (2026-10-22)
- [x] ~~Perjalanan (v0.2) di API: `PUT`, `PATCH`, `DELETE /trips/{id}`, checklist sebagai daftar dengan `trip_id` (tidak dihitung `last_list`, 409 `trip_checklist`), tautan target yang dilepas saat target dihapus, koleksi sync `trips`, preferensi `trip_reminders`, dan pengingat `trips:remind` (H-7 dan H-1 pukul 08.00, dengan progres checklist)~~ (2026-10-22)
- [x] ~~Pengingat setor rutin per target (v0.2): kontrak disetujui pemilik (tanggal bulanan 1–31, jatuh ke hari terakhir bulan yang lebih pendek), kolom `reminder_frequency` dan `reminder_day`, preferensi `savings_reminders`, dan perintah `savings:remind`; seluruh cakupan v0.2 di server selesai~~ (2026-10-22)
- [x] ~~Perjalanan dan pengingat setor di app Flutter (repo mobile `190de05`): tab Liburan dengan checklist persiapan, kartu Persiapan liburan di Beranda, jadwal setor di Detail target, switch `trip_reminders` dan `savings_reminders`, dan sync penuh sekali untuk `trips`; uji lewat layar dan uji dua HP lulus terhadap API `463522a`~~ (2026-10-23)
- [x] ~~Dokumen perencanaan: panduan mematikan optimasi baterai dihapus dari mitigasi risiko notifikasi, sesuai keputusan pemilik; gantinya push prioritas tinggi dan, kalau perlu, tombol izin dari Android di Pengaturan > Notifikasi~~ (2026-10-23)
- [x] ~~Push pengingat v0.2 (`special_date_reminder`, `trip_reminder`, `savings_reminder`) dikirim manual dari server: ketiganya sampai di HP pemilik dan membuka Beranda, Detail perjalanan, dan Detail target saat diketuk~~ (2026-10-23)
- [x] ~~Kenangan dan foto (v0.3) di API: kontrak disetujui pemilik (10 butir, termasuk pin lokasi menyusul di v1.x), `PUT`, `PATCH`, `DELETE /memories/{id}`, `GET /memories/{id}/photos` untuk URL baru, unggah `POST /memory-photos/{id}` (JPEG tanpa metadata, versi penuh 2560 px dan thumbnail 800 px, paling banyak 50 foto per kenangan), `PATCH` dan `DELETE /memory-photos/{id}`, file dibuang dari R2 lewat job, koleksi sync `memories` dan `memory_photos`, dan push `memory_created` 2 menit setelah kenangan dibuat~~ (2026-10-23)
- [x] ~~Kenangan dan foto (v0.3) di app Flutter (repo mobile `main`): tab Kenangan, Detail kenangan, galeri, "Simpan jadi kenangan", dan unggah foto lewat antrean; build rilis terpasang di HP pemilik, dan uji ujung ke ujung terhadap API lokal lulus (unggah foto sungguhan menunggu uji di server)~~ (2026-10-24)
- [x] ~~Penjadwal pengingat v0.2 di HP: `trip_reminder` dan `special_date_reminder` (H-1 dan hari H) sampai di HP pemilik pada Sabtu 24 Oktober pukul 08.00~~ (2026-10-24)
- [x] ~~Tugas berulang (v1.0) di API: kontrak disetujui pemilik (10 butir, termasuk "setiap N" 1–99 dan mingguan satu hari dulu), kolom `repeat_frequency`, `repeat_interval`, `repeat_anchor`, dan `next_task_id`, tugas berikutnya dibuat server sekali saat selesai, Urungkan yang mengambil kembali tugas berikutnya yang belum disentuh, dan `next_task` di balasan tugas~~ (2026-10-24)
- [x] ~~Cek keamanan Oktober 2026: Laravel 13.35.0 sudah lewat perbaikan TokenGuard (13.29.0) dan XSS halaman debug (13.30.0), PHP 8.4.27 di server dan laptop sudah lewat PHP2Shell, `npm audit` bersih, 179 paket Dart tanpa celah tercatat di OSV, dan Livewire dinaikkan ke 4.4.8 untuk GHSA-mhf7-xp6r-6qrc (tidak bisa dipakai di sini, karena `#[Authorize]` dan `#[Json]` tidak dipakai)~~ (2026-10-24)
- [x] ~~Audit keamanan menyeluruh repo API dan server: riwayat git bersih dari rahasia, file sensitif di server 404, `APP_DEBUG` mati, dan cookie sesi `secure`. Yang diperbaiki: `position` tak hingga (500 menjadi 422), login dengan email berupa array (500 menjadi 422), host header injection lewat `X-Forwarded-Host`, `Port`, dan `Prefix`, header keamanan, halaman bawaan Laravel di `/`, `robots.txt`, serta token FCM tidak valid yang kini dihapus dan kegagalan FCM lain yang kini dicatat~~ (2026-10-24)
- [x] ~~Server mati (502) dari deploy `f0c27ae` pada 24 Oktober sekitar pukul 17.55 sampai 25 Oktober pukul 16.13 WIB: menghapus `welcome.blade.php` membuat folder `resources/views` hilang dari repo, `view:cache` di skrip start gagal, dan container berhenti. Diperbaiki di `f5d2856` dengan `resources/views/.gitkeep` dan tes yang menjalankan `optimize` seperti container. Selama mati, sync tertahan di antrean HP (terkirim begitu server pulih), sedangkan push dan pengingat pada jam itu tidak terkirim. Semua perbaikan audit sudah dicek di produksi sesudahnya~~ (2026-10-25)
- [x] ~~Coolify diperbarui otomatis ke v4.5.0, yang dibangun di atas rilis keamanan v4.4.7 (PHP 8.4.27 untuk PHP2Shell, Node 24.22.1, cloudflared 2026.10.3, Sentinel 1.1.2); server tetap sehat sesudahnya, dan `docker exec coolify php -v` di server membalas PHP 8.4.27~~ (2026-10-25)
- [x] ~~Container tunnel memakai cloudflared 2026.10.3 (dibangun 21 Oktober 2026), jadi CA eMudhra yang bocor tidak dipercaya lagi; dicek dengan `cloudflared --version` di dalam container itu~~ (2026-10-25)
- [x] ~~Node di laptop sudah 22.23.4, rilis keamanan 21 Oktober, dari installer resmi di `C:\Program Files\nodejs` (npm 11.19.1). Node 23.11.x dari Herd masih terpasang tetapi tidak aktif; versi 23 tidak lagi mendapat perbaikan keamanan sejak Juni 2025, jadi sebaiknya dihapus dari Herd~~ (2026-10-25)
- [x] ~~Alamat `/` mengalihkan ke halaman masuk panel admin atas permintaan pemilik, menggantikan balasan JSON nama API; app tidak memakai `/`, hanya `/api/v1`~~ (2026-10-25)
- [x] ~~Push sinkron (v1.x): push `sync` berisi data saja ke anggota lain setiap Ruang Bersama berubah, paling banyak satu per 10 detik per penerima, dengan perubahan pertama dikirim langsung. Kontraknya disetujui 25 Oktober~~ (2026-10-26)
