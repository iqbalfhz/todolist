# Kontrak API v0.1

> Salinan dari dokumen perencanaan di claude.ai (tab "Kontrak API v0.1"), per 30 Oktober 2026. Kalau berbeda, dokumen itu yang berlaku. Setiap kali kontrak berubah, perbarui salinan ini di repo `api` dan `mobile`.

Rilis v0.1 butuh 25 endpoint di bawah `/api/v1`: semua data dibaca lewat satu pintu, `GET /sync`, dan setiap perubahan data dari antrean offline aman dikirim ulang. Laravel membangunnya persis seperti di sini, dan Flutter memanggilnya dengan bentuk data yang sama. Setelah endpoint jadi, dokumentasi otomatis Scramble menjadi acuan harian. Rilis v0.2 menambah 6 endpoint: 3 untuk Tanggal penting dan 3 untuk Perjalanan. Rilis v0.3 menambah 7 endpoint untuk Kenangan dan fotonya. Rilis v1.0 dimulai dari tugas berulang, tanpa endpoint baru, lalu keluar dari Ruang Bersama, hapus akun, dan ekspor data, yang masing-masing menambah satu endpoint. Rilis v1.x dimulai dari push sinkron, lalu penarikan tabungan, keduanya tanpa endpoint baru. Pin lokasi kenangan menambah satu endpoint baca untuk mencari tempat, dan reaksi serta komentar menambah 5 endpoint tulis. Masuk dengan Google menambah satu endpoint tanpa token.

## Ringkasan endpoint

Semua endpoint butuh token Sanctum, kecuali daftar, masuk, dan masuk dengan Google. Hanya enam yang membaca data (`GET /me`, `GET /spaces`, `GET /sync`, `GET /memories/{id}/photos` sejak v0.3, `GET /places` sejak v1.x, dan `GET /me/export` sejak v1.0); sisanya menulis.

| Modul | Method | Endpoint | Fungsi |
| --- | --- | --- | --- |
| Akun | POST | `/auth/register` | Daftar, membuat Ruang Pribadi, menerima token |
| Akun | POST | `/auth/login` | Masuk, menerima token |
| Akun | POST | `/auth/google` | Masuk atau daftar dengan akun Google, menerima token (v1.x) |
| Akun | POST | `/auth/logout` | Mencabut token HP ini dan menghapus token FCM-nya |
| Akun | GET | `/me` | Profil sendiri, preferensi notifikasi, dan kode undangan aktif |
| Akun | PATCH | `/me` | Ubah nama, warna diri, zona waktu, mata uang, preferensi, tanggal lahir |
| Akun | POST | `/me/avatar` | Unggah foto profil |
| Akun | DELETE | `/me/avatar` | Hapus foto profil |
| Akun | DELETE | `/me` | Hapus akun beserta semua datanya (v1.0) |
| Akun | GET | `/me/export` | Unduh salinan semua data akun sebagai satu file JSON (v1.0) |
| Perangkat | POST | `/devices` | Daftarkan token FCM HP ini |
| Perangkat | DELETE | `/devices/{token}` | Hapus token FCM |
| Ruang | GET | `/spaces` | Ruang tempatmu menjadi anggota, beserta profil anggotanya |
| Ruang | POST | `/invitations` | Ambil kode undangan aktif, atau buat yang baru |
| Ruang | POST | `/invitations/accept` | Pakai kode; Ruang Bersama dibuat untuk kalian berdua |
| Ruang | DELETE | `/spaces/{id}` | Keluar dari Ruang Bersama; ruang ditutup untuk kalian berdua (v1.0) |
| Sinkron | GET | `/sync` | Semua data yang berubah sejak kursor |
| Daftar | PUT | `/lists/{id}` | Buat daftar tugas |
| Daftar | PATCH | `/lists/{id}` | Ubah daftar, termasuk urutan dan arsip |
| Daftar | DELETE | `/lists/{id}` | Hapus daftar beserta tugasnya |
| Tugas | PUT | `/tasks/{id}` | Buat tugas |
| Tugas | PATCH | `/tasks/{id}` | Ubah, selesaikan, atau batalkan selesai |
| Tugas | DELETE | `/tasks/{id}` | Hapus tugas |
| Tabungan | PUT | `/savings-goals/{id}` | Buat target tabungan |
| Tabungan | PATCH | `/savings-goals/{id}` | Ubah target |
| Tabungan | DELETE | `/savings-goals/{id}` | Hapus target beserta setorannya |
| Tabungan | PUT | `/savings-entries/{id}` | Catat setoran |
| Tabungan | PATCH | `/savings-entries/{id}` | Ubah setoran |
| Tabungan | DELETE | `/savings-entries/{id}` | Hapus setoran |
| Tanggal penting | PUT | `/special-dates/{id}` | Buat tanggal penting (v0.2) |
| Tanggal penting | PATCH | `/special-dates/{id}` | Ubah tanggal penting (v0.2) |
| Tanggal penting | DELETE | `/special-dates/{id}` | Hapus tanggal penting (v0.2) |
| Perjalanan | PUT | `/trips/{id}` | Buat perjalanan (v0.2) |
| Perjalanan | PATCH | `/trips/{id}` | Ubah perjalanan (v0.2) |
| Perjalanan | DELETE | `/trips/{id}` | Hapus perjalanan beserta checklistnya (v0.2) |
| Kenangan | PUT | `/memories/{id}` | Buat kenangan (v0.3) |
| Kenangan | PATCH | `/memories/{id}` | Ubah kenangan (v0.3) |
| Kenangan | DELETE | `/memories/{id}` | Hapus kenangan beserta fotonya (v0.3) |
| Kenangan | GET | `/memories/{id}/photos` | Foto kenangan dengan URL baru (v0.3) |
| Kenangan | POST | `/memory-photos/{id}` | Unggah foto kenangan (v0.3) |
| Kenangan | PATCH | `/memory-photos/{id}` | Ubah urutan foto (v0.3) |
| Kenangan | DELETE | `/memory-photos/{id}` | Hapus foto (v0.3) |
| Kenangan | GET | `/places` | Cari tempat untuk pin lokasi kenangan (v1.x) |
| Komentar | PUT | `/comments/{id}` | Tulis komentar (v1.x) |
| Komentar | PATCH | `/comments/{id}` | Ubah komentar sendiri (v1.x) |
| Komentar | DELETE | `/comments/{id}` | Hapus komentar sendiri (v1.x) |
| Reaksi | PUT | `/reactions/{id}` | Beri reaksi, atau ganti reaksi sendiri (v1.x) |
| Reaksi | DELETE | `/reactions/{id}` | Hapus reaksi sendiri (v1.x) |

## Konvensi

Semua request dan balasan memakai JSON dengan nama kolom `snake_case`, sama dengan kamus data di tab utama. Aturan di bagian ini berlaku untuk semua endpoint.

### Header

| Header | Isi | Kapan |
| --- | --- | --- |
| `Accept` | `application/json` | Selalu |
| `Authorization` | `Bearer <token>` | Semua endpoint kecuali daftar, masuk, dan masuk dengan Google |
| `Content-Type` | `application/json` | Request yang punya body; `multipart/form-data` untuk foto profil dan foto kenangan |

Pesan error selalu berbahasa Indonesia (`APP_LOCALE=id`, terjemahan dari paket laravel-lang). Semua route `api/*` dibalas JSON walau header `Accept` terlewat, lewat `shouldRenderJsonWhen()` di `bootstrap/app.php`.

### Bentuk balasan

- Satu data: `{"data": {...}}`. Tugas (v1.0): `{"data": {...}, "next_task": {...}}`. Sinkron: `{"data": {...}, "meta": {...}}`.
- Kolom kosong tetap dikirim sebagai `null`, supaya model freezed di Flutter selalu lengkap.
- `DELETE` dan `POST /auth/logout` membalas 204 tanpa body.
- Angka desimal seperti `position` bisa tiba sebagai `1024` atau `1024.5`; di Dart baca sebagai `num`. Nilainya harus di antara -10^15 dan 10^15; di luar itu, termasuk angka yang terlalu besar untuk disimpan, ditolak 422.

### ID

- Semua ID berupa ULID 26 karakter huruf kecil, contoh `01m4dg5jegv6sp4xav8efqzcqe`.
- ID daftar, tugas, target, setoran, tanggal penting, perjalanan, kenangan, foto kenangan, komentar, dan reaksi dibuat di HP; ID akun, ruang, dan undangan dibuat server.
- ULID huruf besar ditolak 422. HP membuat huruf kecil sejak awal, supaya kunci di SQLite sama dengan di server.

### Waktu, tanggal, dan teks

| Jenis | Format | Contoh | Zona |
| --- | --- | --- | --- |
| Waktu dari server | ISO 8601 dengan mikrodetik | `2026-10-08T10:15:30.123456Z` | UTC |
| Waktu dari HP | ISO 8601 dengan `Z` atau offset; tanpa itu ditolak 422 | `2026-10-08T10:15:30Z` | Apa saja, disimpan UTC |
| Tanggal | `YYYY-MM-DD` | `2026-10-09` | Zona waktu ruang |
| Jam | `HH:MM`, 24 jam | `17:00` | Zona waktu ruang |

- `created_at`, `updated_at`, dan `deleted_at` selalu diisi server dengan presisi mikrodetik (`timestamps(6)`, `softDeletes(precision: 6)`, dan `$dateFormat = 'Y-m-d H:i:s.u'` di model), supaya urutan sinkron tepat.
- Di Dart, kirim waktu dengan `toUtc().toIso8601String()`.
- Di Laravel, API Resource memformat tanggal dan jam secara eksplisit (`Y-m-d`, `H:i`), dan `APP_TIMEZONE` tetap `UTC`.
- Panjang teks dihitung dalam code point Unicode, sehingga `max` di Laravel dan `runes.length` di Dart memberi angka yang sama.

### Uang

Nominal disimpan sebagai bilangan bulat dalam satuan terkecil, ditambah kode ISO 4217 di kolom `currency`. Jumlah desimal mengikuti tabel ini, bukan bawaan library.

| Kode | Desimal | Contoh nilai | Tampil |
| --- | --- | --- | --- |
| IDR | 0 | `150000` | Rp150.000 |
| JPY | 0 | `5000` | ¥5.000 |
| USD, SGD, MYR, EUR | 2 | `1050` | 10,50 |

- IDR sengaja tanpa desimal, berbeda dari ISO 4217. `intl` di Dart dan `NumberFormatter` di PHP memakai 2 desimal untuk IDR, jadi `decimalDigits` selalu diisi dari tabel ini.
- Nominal lebih dari 0 dan paling besar 10^15.
- Tabel ini disalin sebagai konstanta di repo `api` dan `mobile`.

### Aturan tulis

- **Buat:** `PUT /<sumber>/{id}` dengan ID dari HP → 201. Kalau ID itu sudah ada di ruang yang sama, server tidak mengubah apa pun dan membalas data tersimpan (200). Kalau sudah terhapus, 410. Kalau ID itu dipakai data di ruang lain, 409 `id_conflict`.
- **Ubah:** `PATCH` hanya berisi kolom yang berubah; `null` untuk mengosongkan. Kolom hanya-baca dan kolom tak dikenal diabaikan. Kolom yang berpasangan dikirim bersama, misalnya `due_date` dengan `due_time`.
- **Bentrok:** kalau dua HP mengubah kolom yang sama, yang terakhir **sampai di server** yang menang, termasuk perubahan offline yang baru terkirim belakangan. Kolom berbeda tidak saling menimpa.
- **Hapus:** `DELETE` mengisi `deleted_at` (soft delete) dan membalas 204, termasuk saat diulang. Penghapusan berantai (daftar ke tugas, target ke setoran) ikut memperbarui `updated_at` anak-anaknya, supaya ikut tersinkron.
- **Induk terhapus atau diarsipkan:** tugas ke daftar yang sudah dihapus dibalas 409 `parent_deleted`, dan ke daftar yang diarsipkan 409 `parent_archived`, baik saat dibuat maupun dipindah. Setoran ke target yang sudah dihapus, foto ke kenangan yang sudah dihapus, serta komentar dan reaksi ke subjek yang sudah dihapus (v1.x), juga dibalas 409 `parent_deleted`.
- **Akses:** data di ruang yang bukan ruangmu dianggap tidak ada (404, bukan 403). 403 hanya untuk komentar atau reaksi orang lain di ruangmu (v1.x).
- **Lewat antrean:** hanya PUT, PATCH, dan DELETE untuk daftar, tugas, target, setoran, tanggal penting, perjalanan, kenangan, dan komentar (v1.x); PUT dan DELETE untuk reaksi (v1.x); POST, PATCH, dan DELETE untuk foto kenangan; ditambah `PATCH /me`. Endpoint lain dipanggil langsung dan butuh koneksi.

### Batas request

| Endpoint | Batas |
| --- | --- |
| `/auth/login` | 5 per menit per email + IP |
| `/auth/register` | 5 per menit per IP |
| `/auth/google` | 5 per menit per IP (v1.x) |
| `/invitations/accept` | 10 per menit per akun dan per IP |
| Lainnya | 120 per menit per akun, cukup untuk antrean yang menumpuk setelah offline |
| `/places` | 20 per menit per akun (v1.x) |
| `DELETE /me` | 5 per menit per akun (v1.0) |
| `GET /me/export` | 5 per jam per akun (v1.0) |

Di luar `/api/v1` ada `GET /up` (bawaan Laravel) untuk health check Coolify, `GET /` yang mengalihkan ke halaman masuk panel admin, `GET /hapus-akun` yang menjelaskan cara menghapus akun (v1.0), dan `/docs/api` untuk dokumentasi Scramble, yang secara bawaan hanya terbuka di environment lokal.

## Error

Setiap error membawa `message` yang bisa langsung ditampilkan dan `code` untuk logika app. Error validasi menambah `errors` per kolom. Endpoint yang lewat antrean juga menyertakan `current`: data di URL request seperti tersimpan di server (untuk `/me`, data akunmu), atau `null` kalau ID itu belum ada di server.

Contoh untuk `PUT /tasks/{id}` yang belum ada di server, sehingga `current` bernilai `null`:

```json
{
  "message": "Judul wajib diisi.",
  "code": "validation_failed",
  "errors": {
    "title": ["Judul wajib diisi."]
  },
  "current": null
}
```

| Status | `code` | Arti | Yang dilakukan app |
| --- | --- | --- | --- |
| 401 | `unauthenticated` | Token tidak berlaku lagi | Hentikan antrean (isinya tetap tersimpan untuk akun ini), buka layar Masuk |
| 403 | `forbidden` | Tidak boleh melakukan ini, misalnya mengubah komentar orang lain (v1.x) | Buang dari antrean, pakai `current`, tampilkan pesan |
| 404 | `not_found` | Data tidak ada, atau bukan di ruangmu | Buang dari antrean, hapus data itu dari cache |
| 404 | `route_not_found` | Endpoint tidak dikenal, biasanya versi app dan server berbeda | Simpan antrean, coba lagi nanti, tawarkan pembaruan app |
| 409 | `parent_deleted`, `parent_archived` | Induknya (daftar, target, kenangan, atau subjek komentar dan reaksi) sudah dihapus, atau daftarnya diarsipkan | Tugas: pindahkan ke daftar aktif pertama, lalu kirim ulang. Setoran, foto kenangan, dan komentar: buang dan beri tahu. Reaksi: buang |
| 409 | `last_list`, `id_conflict` | Bentrok dengan aturan; rinciannya di bagian masing-masing | Buang dari antrean, pakai `current`, tampilkan pesan |
| 409 | `trip_checklist` (v0.2) | Checklist perjalanan dihapus atau diarsipkan sendiri | Buang dari antrean, pakai `current`, tampilkan pesan |
| 409 | `shared_space_exists` | Sudah punya Ruang Bersama dengan orang lain | Tampilkan pesan; endpoint undangan tidak lewat antrean |
| 410 | `resource_deleted` | Sudah dihapus, biasanya oleh pasangan | Buang dari antrean, hapus data itu dari cache |
| 422 | `validation_failed`, `invalid_credentials`, `invitation_invalid` | Isi request tidak valid | Tampilkan pesan; kalau dari antrean, buang dan pakai `current` |
| 422 | `google_token_invalid` (v1.x) | Token Google salah, kedaluwarsa, untuk app lain, emailnya belum terverifikasi, atau saat hapus akun bukan dari akun Google yang tersambung (v1.0) | Tampilkan pesan; pengguna bisa mencoba masuk dengan Google lagi |
| 429 | `too_many_requests` | Terlalu sering | Tunggu sesuai header `Retry-After`, lalu coba lagi |
| 5xx, tanpa koneksi, atau balasan bukan JSON | `server_error` atau tidak ada | Server, proxy, atau jaringan bermasalah | Coba lagi dengan jeda 2, 4, 8 detik dan seterusnya, maksimal 5 menit |
| 503 | `places_unavailable` (v1.x) | Pencarian tempat sedang tidak tersedia: layanannya gangguan atau dimatikan di server | Tampilkan pesan; pin tetap bisa dipasang langsung di peta |

- App hanya menghapus data atau membuang isi antrean kalau balasannya JSON dengan `code` yang dikenal. 404 atau 502 tanpa JSON, misalnya dari Traefik saat Coolify sedang deploy, diperlakukan seperti 5xx.
- "Pakai `current`" berarti: kalau `null`, hapus versi lokal; kalau terisi, timpa versi lokal dengannya. Kalau DELETE daftar ditolak, app juga memulihkan tugas yang tadi ikut terhapus di cache.
- App memvalidasi dengan aturan yang sama sebelum menyimpan, jadi 422 dari antrean seharusnya tidak terjadi.
- 401 hanya berarti token tidak berlaku. Salah kata sandi dibalas 422 `invalid_credentials`, jadi interceptor dio aman menganggap setiap 401 sebagai "keluar".
- Di Laravel, `code` dan `current` ditambahkan lewat `withExceptions()` di `bootstrap/app.php`, dan `current` diambil dari model di route. `not_found` hanya untuk `ModelNotFoundException`; route yang tidak ada memakai `route_not_found`.

## Akun, profil, dan perangkat

Daftar sekaligus membuat akun, Ruang Pribadi berisi satu daftar "Tugas", dan token untuk HP ini. Satu HP memegang satu token Sanctum yang berlaku sampai keluar.

Request `POST /auth/register` (balasan 201):

```json
{
  "name": "Iqbal",
  "email": "nama@email.com",
  "password": "minimal-8-karakter",
  "device_name": "Redmi Note 13",
  "timezone": "Asia/Jakarta"
}
```

- Balasan: `{"data": {"token": "1|abc...", "user": {...}}}`, dengan `user` berbentuk sumber user di bawah. Setelah itu app langsung menjalankan sync penuh untuk mendapat Ruang Pribadi dan daftarnya.
- `password` minimal 8 karakter. `email` unik; email yang sudah terdaftar ditolak 422 di kolom `email`.
- `timezone` berupa nama zona IANA dari HP (paket `flutter_timezone`); kosong berarti `Asia/Jakarta`.
- `POST /auth/login` memakai `email`, `password`, dan `device_name`, lalu membalas bentuk yang sama (200). Email atau kata sandi salah: 422 `invalid_credentials`.
- `POST /auth/logout` boleh membawa `{"fcm_token": "..."}`. Server mencabut token Sanctum yang dipakai dan menghapus token FCM itu (204). App juga memanggil `FirebaseMessaging.instance.deleteToken()` apa pun hasilnya, supaya HP yang sudah keluar tidak menerima push.
- Cache dan antrean disimpan per akun. Kalau akun lain masuk di HP yang sama, app memakai database lokal yang berbeda, jadi antrean akun sebelumnya tidak terkirim atas nama akun baru.

### Masuk dengan Google (v1.x)

App bisa masuk atau daftar dengan akun Google. App meminta ID token ke Google lewat paket `google_sign_in`, dengan `serverClientId` berisi client ID klien Web, lalu menukarnya dengan token Denly Days. Server memeriksa token itu sendiri, jadi app tidak pernah mengirim email atau nama mentah sebagai bukti.

Request `POST /auth/google`, tanpa token Sanctum:

```json
{
  "id_token": "eyJhbGciOiJSUzI1NiIsImtpZCI6Ij...",
  "device_name": "Redmi Note 14 Pro+ 5G",
  "timezone": "Asia/Jakarta"
}
```

- Balasan berbentuk sama dengan daftar dan masuk, `{"data": {"token": "1|abc...", "user": {...}}}`: 201 kalau akun baru dibuat, 200 kalau masuk ke akun yang sudah ada. Setelah itu app menjalankan sync penuh seperti setelah masuk.
- `id_token` wajib, paling banyak 4.096 karakter. `device_name` dan `timezone` mengikuti aturan daftar; `timezone` hanya dipakai saat akun baru dibuat.
- Server memeriksa tanda tangan token dengan kunci publik Google, masa berlakunya, penerbitnya (`accounts.google.com`), dan `aud` yang harus sama dengan client ID klien Web. Email di token wajib terverifikasi. Token yang tidak lolos dibalas 422 `google_token_invalid`.
- Kunci publik Google disimpan di cache server selama masa berlaku yang diberikan Google. Kalau kunci itu tidak bisa diambil, balasannya 503 `server_error`, dan pengguna bisa mencoba lagi.
- Akun dicari dengan urutan ini:
    1. Akun yang pernah masuk dengan akun Google yang sama (`sub` di token): langsung masuk, walau email akun Google itu sudah berubah.
    2. Akun yang emailnya sama dengan email Google: akun itu disambungkan ke akun Google tersebut, lalu masuk. Kata sandinya tetap berlaku.
    3. Belum ada: akun baru dibuat seperti daftar, dengan Ruang Pribadi berisi daftar "Tugas", nama dari Google (paling banyak 50 karakter; bagian email sebelum @ kalau Google tidak memberi nama), dan tanpa kata sandi.
- Kalau email Google berbeda dari email akun lama, server membuat akun baru yang terpisah. App sebaiknya menyebut akun Google yang sedang dipakai di layar Masuk.
- Akun tanpa kata sandi bisa memakai semua fitur, tetapi hanya bisa masuk dengan Google; masuk dengan kata sandi dibalas `invalid_credentials`. Sampai ada fitur kata sandi di app, admin bisa membuatkannya lewat "Reset kata sandi".
- Server hanya menyimpan ID akun Google (`sub`). Nama dan email diambil saat akun dibuat, dan email akun tidak ikut berubah kalau email Google berubah. Foto profil Google tidak diambil.
- Sumber user tidak berubah, jadi fitur ini tidak membuat app perlu sync penuh.
- Batasnya 5 per menit per IP.
- Server membaca client ID dari `GOOGLE_CLIENT_ID`, dan app memakai nilai yang sama untuk `serverClientId`. Selama layar persetujuan Google berstatus Testing, hanya pengguna uji (pemilik dan pasangan) yang bisa masuk dengan Google.

### Sumber user

Dipakai oleh `GET /me`, `PATCH /me`, dan balasan daftar atau masuk.

```json
{
  "id": "01m4dg5jegv6sp4xav8efqzcqe",
  "name": "Iqbal",
  "email": "nama@email.com",
  "color": "mawar",
  "timezone": "Asia/Jakarta",
  "locale": "id",
  "default_currency": "IDR",
  "birth_date": "--03-02",
  "notification_prefs": {
    "task_reminders": true,
    "partner_activity": true,
    "savings_activity": true,
    "special_dates": true,
    "trip_reminders": true,
    "savings_reminders": true
  },
  "avatar_url": null,
  "avatar_version": 0,
  "invitation": {
    "code": "K7M2QX",
    "expires_at": "2026-10-10T10:16:00.000000Z"
  },
  "created_at": "2026-10-08T10:15:30.000000Z",
  "updated_at": "2026-10-08T10:15:30.000000Z"
}
```

| Kolom yang bisa diubah lewat `PATCH /me` | Aturan |
| --- | --- |
| `name` | 1–50 karakter; nama panggilan yang dilihat pasangan |
| `color` | `mawar`, `ungu`, `toska`, `bata`, `biru`, atau `lavender` |
| `timezone` | Nama zona IANA. Zona Ruang Pribadi ikut berubah, dan `remind_at` tugasnya dihitung ulang lewat job antrean |
| `default_currency` | Salah satu kode di tabel Uang; jadi bawaan target baru |
| `birth_date` | `YYYY-MM-DD`, atau `--MM-DD` kalau tahun tidak diisi; `null` untuk menghapus |
| `notification_prefs` | Objek; hanya kunci yang dikirim yang berubah |

- `invitation` adalah kode undanganmu yang masih berlaku, atau `null`. Rinciannya di bagian Ruang dan undangan.
- `birth_date` dicek dengan pola dan kalender (`--02-30` ditolak, `--02-29` boleh), lalu disimpan sebagai teks. Pengingat v0.2 memakai bagian `MM-DD`; 29 Februari diingatkan 28 Februari di tahun biasa.
- Email dan kata sandi belum bisa diubah di v0.1. Tema (Mawar atau Anggrek, terang atau gelap) disimpan di HP saja.

### Foto profil

- `POST /me/avatar`, multipart, kolom `photo`: JPEG, PNG, atau WebP, maksimal 2 MB. App memotongnya persegi dan mengecilkan ke 512 × 512 sebelum unggah.
- Server langsung menyimpan ulang sebagai JPEG 512 × 512 di R2, sehingga metadata EXIF (termasuk lokasi) terbuang. Lalu `avatar_version` naik satu, dan balasannya sumber user (200).
- `DELETE /me/avatar` → 204. App kembali menampilkan inisial dengan warna diri.
- `avatar_url` adalah URL R2 bertanda tangan yang berlaku 7 hari, batas terlama R2. App menyimpan gambarnya di cache dengan kunci `avatar-{id}-{avatar_version}`. Untuk mendapat URL baru, app memanggil `GET /spaces` setiap kali dibuka dari awal, dan saat R2 membalas 403.

### Perangkat

- `POST /devices` dengan `{"token": "<token FCM>", "platform": "android"}` → 201 kalau baru, 200 kalau sudah ada. Token yang tercatat milik akun lain dipindah ke akun ini (HP berganti akun).
- App mengirim ulang setiap kali FCM memberi token baru.
- `DELETE /devices/{token}` → 204, hanya untuk token milik akunmu sendiri. Server juga menghapus token yang ditolak FCM sebagai `UNREGISTERED` atau sebagai token yang tidak valid.

### Hapus akun (v1.0)

Akun bisa dihapus dari app kapan saja. Semua datanya langsung dihapus permanen, bukan dinonaktifkan, termasuk isi Ruang Bersama, yang ikut hilang untuk pasangan.

| Endpoint | Body | Berhasil | Ditolak |
| --- | --- | --- | --- |
| `DELETE /me` | `{"password": "..."}`, atau `{"id_token": "..."}` dari Masuk dengan Google untuk akun yang tersambung ke Google | 204 | 422 `invalid_credentials` ("Kata sandi salah."), 422 `google_token_invalid`, 422 `validation_failed` (keduanya kosong), 429 `too_many_requests` |

- Endpoint ini tidak lewat antrean dan butuh koneksi. Kalau keduanya dikirim, server memakai kata sandi. Token Google harus dari akun Google yang tersambung ke akun ini; token dari akun Google lain dibalas `google_token_invalid` ("Pilih akun Google yang tersambung ke akun ini.").
- Di app, hapus akun memakai konfirmasi yang menyebut akibatnya: semua data hilang permanen dan tidak bisa dibatalkan, dan Ruang Bersama ditutup dengan isinya ikut terhapus untuk pasangan. Konfirmasi itu juga menyarankan ekspor data dulu.
- Yang langsung dihapus permanen: akun dan profil, foto profil, Ruang Pribadi beserta seluruh isi dan fotonya, token di semua HP, token perangkat, dan kode undangan.
- Ruang Bersama ditutup untuk kalian berdua seperti keluar, tetapi isinya beserta foto langsung dihapus permanen, tanpa masa 30 hari, karena tidak bisa dipulihkan tanpa akun itu. Ruang Bersama lama yang sudah ditutup juga ikut dikosongkan. Ruangnya sendiri tetap ada tanpa isi sampai dihapus `spaces:purge`, supaya HP pasangan menghapusnya lewat sync.
- Pasangan menerima push `partner_left` dengan teks "{nama} menghapus akunnya, jadi Ruang Bersama kalian ditutup."
- Setelah 204, app menghapus semua data lokal akun itu (database, antrean, dan cache foto) lalu kembali ke layar Masuk. HP lain milikmu mendapat 401 pada request berikutnya. Kalau balasan 204 hilang dan app mengirim ulang, server membalas 401; app yang sudah meminta hapus akun menganggapnya selesai.
- Email dan akun Google yang sama bisa dipakai lagi untuk daftar sebagai akun baru, tanpa data lama.
- Cadangan database tidak diubah, jadi data yang dihapus baru hilang dari sana saat cadangan lama terhapus otomatis, paling lama 30 hari kemudian.
- **Tanpa app:** halaman publik `GET /hapus-akun`, yang dibutuhkan Google Play, menjelaskan cara menghapus dari app dan cara memintanya lewat email dari alamat akun ke alamat kontak di `SUPPORT_EMAIL`. Admin memprosesnya paling lambat 3 × 24 jam lewat aksi "Hapus akun" di panel, dengan proses yang sama.

### Ekspor data (v1.0)

Salinan semua data yang bisa kamu lihat di app bisa diunduh kapan saja sebagai satu file JSON, misalnya sebelum keluar dari Ruang Bersama atau menghapus akun.

| Endpoint | Berhasil | Ditolak |
| --- | --- | --- |
| `GET /me/export` | 200 berisi file JSON, dengan `Content-Disposition: attachment; filename="denly-days-2026-10-31.json"`; tanggalnya di zona waktu akun | 429 `too_many_requests` (lebih dari 5 per jam) |

```json
{
  "app": "Denly Days",
  "format_version": 1,
  "exported_at": "2026-10-31T08:00:00.000000Z",
  "user": {},
  "devices": [
    {
      "platform": "android",
      "created_at": "2026-10-08T10:20:00.000000Z",
      "updated_at": "2026-10-08T10:20:00.000000Z"
    }
  ],
  "spaces": [],
  "task_lists": [],
  "tasks": [],
  "savings_goals": [],
  "savings_entries": [],
  "special_dates": [],
  "trips": [],
  "memories": [],
  "memory_photos": [],
  "comments": [],
  "reactions": []
}
```

- Endpoint ini tidak lewat antrean dan butuh koneksi. Balasannya langsung berupa file, tanpa pembungkus `data`, supaya file yang disimpan app bisa berdiri sendiri. `format_version` naik kalau bentuk file berubah.
- `user` memakai sumber user. `spaces` berisi ruang yang masih terbuka beserta profil anggotanya, dan koleksi lain memakai sumber yang sama dengan sync, diurutkan menurut waktu dibuat.
- Isinya sama dengan yang terlihat di app: Ruang Pribadi dan Ruang Bersama beserta seluruh isinya yang belum dihapus, termasuk data buatan pasangan di Ruang Bersama. Isi Ruang Pribadi pasangan, data yang sudah dihapus, dan Ruang Bersama yang sudah ditutup tidak ikut.
- Kata sandi, token masuk, token FCM, dan ID akun Google tidak ikut. `devices` hanya menyebut platform dan waktunya.
- Foto tidak ikut sebagai file. Setiap baris `memory_photos` membawa `url` dan `thumb_url` baru yang berlaku 7 hari, jadi app bisa menawarkan unduh semua foto. Foto profil ikut lewat `avatar_url` di `user`.
- Di app, Pengaturan > Ekspor data menyimpan file itu ke HP, lalu menawarkan unduh fotonya. Konfirmasi keluar dari Ruang Bersama dan hapus akun menyarankan ekspor dulu, dan halaman `/hapus-akun` juga menyebutnya.
- File ini memenuhi hak atas salinan data (Pasal 7 UU PDP) dalam format yang dapat dibaca sistem elektronik (Pasal 13), langsung tanpa menunggu batas 3 × 24 jam di Pasal 32. Impor dari file ini belum ada.

## Ruang dan undangan

Ruang Bersama baru dibuat saat pasangan memakai kode undangan, jadi ruang itu selalu berisi tepat 2 orang. Setiap akun punya tepat satu Ruang Pribadi dan, di v0.1, paling banyak satu Ruang Bersama. Ruang Bersama yang sudah ditutup (v1.0) tidak dihitung.

### Sumber ruang

```json
{
  "id": "01m4dgdt40te98hy85v3fe2t18",
  "name": "Ruang Bersama",
  "type": "shared",
  "timezone": "Asia/Jakarta",
  "owner_id": "01m4dg5jegv6sp4xav8efqzcqe",
  "members": [
    {
      "id": "01m4dg5jegv6sp4xav8efqzcqe",
      "name": "Iqbal",
      "color": "mawar",
      "avatar_url": null,
      "avatar_version": 0,
      "birth_date": "--03-02",
      "role": "owner",
      "joined_at": "2026-10-08T10:20:00.000000Z"
    },
    {
      "id": "01m4dga4y03c5ratqsc31zc916",
      "name": "Nama Pasangan",
      "color": "ungu",
      "avatar_url": null,
      "avatar_version": 0,
      "birth_date": null,
      "role": "member",
      "joined_at": "2026-10-08T10:20:00.000000Z"
    }
  ],
  "created_at": "2026-10-08T10:20:00.000000Z",
  "updated_at": "2026-10-08T10:20:00.000000Z",
  "deleted_at": null
}
```

- `type` berisi `personal` atau `shared`. Ruang Pribadi mengikuti zona waktu pemiliknya; Ruang Bersama memakai zona waktu pengundang dan tidak berubah di v0.1.
- Perubahan nama, warna, foto, atau tanggal lahir (v0.2) seorang anggota ikut memperbarui `updated_at` ruangnya, supaya sampai ke HP pasangan lewat sync.
- `GET /spaces` membalas semua ruang tempatmu menjadi anggota (1 atau 2), tanpa paginasi.
- `deleted_at` terisi berarti Ruang Bersama sudah ditutup (v1.0). Ruang seperti itu hanya muncul lewat sync, sebagai tanda untuk menghapusnya dari HP; rinciannya di Keluar dari Ruang Bersama.

### Endpoint undangan

| Endpoint | Body | Berhasil | Ditolak |
| --- | --- | --- | --- |
| `POST /invitations` | kosong, atau `{"renew": true}` | 200 berisi kode aktif kalau masih ada; 201 kalau kode baru dibuat. `renew` mencabut kode lama lalu membuat yang baru | 409 `shared_space_exists` (sudah punya pasangan) |
| `POST /invitations/accept` | `{"code": "k7m2qx"}` | 201 berisi Ruang Bersama yang baru dibuat. Diulang setelah berhasil: 200 berisi ruang yang sama | 409 `shared_space_exists`, 422 `invitation_invalid` |

### Aturan undangan

- Kode: 6 karakter dari `23456789ABCDEFGHJKLMNPQRSTUVWXYZ` (tanpa 0, O, 1, dan I), berlaku 48 jam, sekali pakai. Server menyeragamkan input: huruf besar, tanpa spasi dan tanda hubung.
- Urutan cek saat menerima: (1) kalau kamu sudah satu Ruang Bersama dengan pembuat kode ini, balas 200 berisi ruang itu; (2) kalau sudah punya Ruang Bersama dengan orang lain, 409 `shared_space_exists`; (3) semua masalah pada kode (salah, kedaluwarsa, sudah dipakai, dicabut, atau milik sendiri) dibalas sama: 422 `invitation_invalid`, "Kode tidak berlaku, minta kode baru."
- Semua langkah terima berjalan dalam satu transaksi yang lebih dulu mengunci baris akun kalian berdua (urut ID), lalu baris undangan. Server membuat Ruang Bersama (pemilik = pengundang), menambahkan kedua anggota, dan membuat daftar "Tugas". Kode ditandai terpakai, dan kode aktif lain milik kalian berdua dicabut.
- Karena itu, kalau kalian sama-sama memasukkan kode pasangan, yang kedua tetap mendapat 200 berisi ruang yang sama, tanpa ruang ganda.
- Kalau warna diri kalian sama, warna yang bergabung diganti ke warna pertama yang belum dipakai, supaya Aku dan Pasangan tetap terbedakan.
- Pengundang menerima push `partner_joined`. Kedua HP lalu menjalankan sync biasa; semua isi ruang baru lebih muda dari kursor masing-masing, jadi sync penuh tidak perlu.
- `space_invitations.space_id` baru terisi saat kode diterima.
- Kalau kalian berdua punya Ruang Bersama yang ditutup kurang dari 30 hari lalu (v1.0), menerima kode memulihkan ruang itu, bukan membuat yang baru. Balasannya tetap 201, berisi ruang lama dengan `created_at` aslinya, dan pengundang menerima `partner_joined` dengan teks bergabung lagi. Rinciannya di Keluar dari Ruang Bersama.

### Keluar dari Ruang Bersama (v1.0)

Salah satu dari kalian bisa keluar kapan saja, tanpa persetujuan pasangan. Ruang Bersama lalu ditutup untuk kalian berdua: isinya tidak bisa dibuka lagi dari HP mana pun, tetapi disimpan server 30 hari, supaya kembali utuh kalau kalian bergabung lagi.

| Endpoint | Body | Berhasil | Ditolak |
| --- | --- | --- | --- |
| `DELETE /spaces/{id}` | kosong | 204, juga saat diulang dan saat pasangan sudah lebih dulu keluar | 404 `not_found` (bukan ruangmu), 422 `validation_failed` (Ruang Pribadi) |

- Endpoint ini tidak lewat antrean dan butuh koneksi. Sebelum memanggilnya, app mengirim isi antreannya dulu, supaya perubahan terakhir ikut tersimpan.
- Di app, keluar memakai konfirmasi yang menyebut akibatnya: Ruang Bersama ditutup untuk kalian berdua, isinya disimpan 30 hari dan kembali utuh kalau kalian bergabung lagi, lalu dihapus permanen beserta fotonya. Konfirmasi itu juga menyarankan ekspor data dulu (v1.0).
- Server mengisi `deleted_at` ruang itu. Sejak saat itu ruang dan semua isinya tidak ada lagi untuk kalian berdua: `GET /spaces` tidak menampilkannya, dan setiap tulis ke sana dibalas 404. Operasi antrean di HP mana pun yang belum terkirim ikut hilang, karena app membuangnya setelah 404.
- Setelah 204, app menghapus ruang itu beserta isinya dari cache. HP lain milikmu dan HP pasangan mendapat ruang itu dengan `deleted_at` terisi lewat sync, lalu melakukan hal yang sama.
- Pasangan menerima push `partner_left`. Pengingat dan push lain untuk ruang itu berhenti, dan yang terlewat selama ditutup tidak dikirim menyusul.
- Ruang yang ditutup tidak dihitung sebagai Ruang Bersama, jadi kalian berdua bisa langsung membuat atau memakai kode undangan, dengan siapa pun.
- **Dipulihkan:** kalau kalian berdua bergabung lagi lewat kode undangan dalam 30 hari, siapa pun pembuat kodenya, server memulihkan ruang lama, bukan membuat yang baru. Isinya kembali seperti saat ditutup; yang sudah dihapus sebelumnya tetap terhapus.
- **Dihapus permanen:** lewat 30 hari, server menghapus ruang beserta seluruh isinya dari database dan semua fotonya dari R2, paling lambat sehari kemudian. Setelah itu tidak bisa dipulihkan, juga oleh admin.
- Ruang Pribadi kalian tidak berubah.
- Hapus akun (v1.0) juga menutup Ruang Bersama, tetapi isinya langsung dihapus permanen; rinciannya di Hapus akun.

## Daftar dan tugas

Setiap ruang selalu punya minimal satu daftar aktif (tidak diarsipkan). Tambah cepat (FR-06) memasukkan tugas ke daftar aktif dengan `position` terkecil di ruang yang dipilih.

### Sumber daftar

```json
{
  "id": "01m4dh0420zqexwxs1n4yw5jn2",
  "space_id": "01m4dgdt40te98hy85v3fe2t18",
  "trip_id": null,
  "name": "Belanja",
  "emoji": "🛒",
  "color": "toska",
  "position": 1024,
  "archived_at": null,
  "created_by": "01m4dg5jegv6sp4xav8efqzcqe",
  "created_at": "2026-10-08T10:30:00.000000Z",
  "updated_at": "2026-10-08T10:30:00.000000Z",
  "deleted_at": null
}
```

| Kolom | Saat PUT | Aturan |
| --- | --- | --- |
| `space_id` | wajib | Ruang tempatmu menjadi anggota; tidak bisa diubah |
| `name` | wajib | 1–50 karakter |
| `emoji` | opsional | Paling banyak 16 code point; app memastikan isinya satu emoji |
| `color` | opsional | `mawar`, `ungu`, `toska`, `bata`, `biru`, atau `lavender`; bawaan `mawar` |
| `position` | wajib | Angka desimal, dihitung app |
| `archived_at` | – | Hanya lewat PATCH: isi untuk mengarsipkan, `null` untuk mengeluarkan |
| `trip_id` | opsional | Perjalanan di ruang yang sama; hanya bisa diisi saat daftar dibuat. Terisi berarti daftar ini checklist persiapan (v0.2) |

- `DELETE /lists/{id}` ikut menghapus semua tugas di dalamnya (soft delete) dan memperbarui `updated_at` tugas-tugas itu.
- Menghapus atau mengarsipkan daftar aktif terakhir di sebuah ruang ditolak 409 `last_list`. Server mengunci baris ruang saat menghitung, supaya dua penghapusan bersamaan tidak sama-sama lolos. Checklist perjalanan tidak dihitung sebagai daftar aktif (v0.2).
- Tugas di daftar yang diarsipkan tidak tampil di tampilan mana pun dan tidak diingatkan, sampai daftarnya dikeluarkan dari arsip.

### Sumber tugas

```json
{
  "id": "01m4dh1yn0t6cdn39q4f091895",
  "task_list_id": "01m4dh0420zqexwxs1n4yw5jn2",
  "space_id": "01m4dgdt40te98hy85v3fe2t18",
  "title": "Beli galon",
  "notes": null,
  "due_date": "2026-10-09",
  "due_time": "17:00",
  "repeat_frequency": "weekly",
  "repeat_interval": 1,
  "priority": 0,
  "position": 2048,
  "assignee_ids": ["01m4dga4y03c5ratqsc31zc916"],
  "remind_at": "2026-10-09T10:00:00.000000Z",
  "completed_at": null,
  "completed_by": null,
  "next_task_id": null,
  "created_by": "01m4dg5jegv6sp4xav8efqzcqe",
  "updated_by": "01m4dg5jegv6sp4xav8efqzcqe",
  "created_at": "2026-10-08T10:31:00.000000Z",
  "updated_at": "2026-10-08T10:31:00.000000Z",
  "deleted_at": null
}
```

| Kolom | Saat PUT | Aturan |
| --- | --- | --- |
| `task_list_id` | wajib | Daftar aktif; `space_id` ikut dari daftarnya. Lewat PATCH hanya bisa pindah ke daftar di ruang yang sama |
| `title` | wajib | 1–200 karakter |
| `notes` | opsional | Paling banyak 5.000 karakter |
| `due_date` | opsional | `YYYY-MM-DD`. Mengosongkannya ikut mengosongkan `due_time` dan aturan ulang |
| `due_time` | opsional | `HH:MM` dalam zona waktu ruang; butuh `due_date` |
| `priority` | opsional | 0 tanpa, 1 rendah, 2 sedang, 3 tinggi; bawaan 0 |
| `position` | wajib | Angka desimal, dihitung app |
| `assignee_ids` | opsional | Larik berisi 1–2 anggota ruang; tidak diisi saat PUT berarti pembuat. Dua ID berarti "Kita". `null` atau `[]` ditolak 422 |
| `completed_at` | opsional | Waktu dicentang di HP, dengan `Z` atau offset; `null` berarti batal selesai. Server mengisi `completed_by` |
| `repeat_frequency` | opsional | `daily`, `weekly`, `monthly`, `yearly`, atau `null` (tidak berulang, bawaan); butuh `due_date` (v1.0) |
| `repeat_interval` | opsional | Setiap berapa hari, minggu, bulan, atau tahun: 1–99, bawaan 1; `null` kalau tidak berulang (v1.0) |
| `next_task_id` | – | Diisi server: tugas berikutnya yang dibuat saat tugas berulang ini selesai (v1.0) |

### Pengingat

- `remind_at` dihitung server: `due_date` + `due_time` di zona waktu ruang, lalu diubah ke UTC. Tugas dengan tanggal tapi tanpa jam diingatkan pukul 08.00 di hari itu. Tanpa `due_date`, nilainya `null`.
- Mengubah `due_date` atau `due_time` menghitung ulang `remind_at` dan mereset status sudah diingatkan.
- Tugas yang selesai, terhapus, atau ada di daftar arsip tidak diingatkan. Pengingat yang telat lebih dari 30 menit, misalnya karena server sempat mati, ditandai terkirim tanpa push.
- Scheduler mengklaim tiap pengingat dengan `UPDATE ... WHERE reminder_sent_at IS NULL` tanpa menyentuh `updated_at`, dan berjalan dengan `withoutOverlapping()`. Pengingat jadi tidak terkirim ganda dan tidak memicu sync.

### Aturan lain

- Pilihan Pasangan dan Kita hanya ada di Ruang Bersama. Memilihnya di lembar tambah tugas memindahkan pilihan daftar ke Ruang Bersama.
- Mengubah `assignee_ids` ikut memperbarui `updated_at` tugasnya, karena tabel `task_assignees` tidak punya kursor sendiri.
- `completed_at` yang lebih maju dari jam server diturunkan menjadi waktu server, tidak ditolak.
- Tampilan Semua, Belum selesai, Selesai, dan Hari Ini (termasuk Terlewat) dihitung di HP dari cache, dengan "hari ini" menurut zona waktu ruang (paket `timezone`).
- Tugas yang dibuat atau dipindah ke daftar yang sudah dihapus dibalas 409 `parent_deleted`, dan ke daftar yang diarsipkan 409 `parent_archived`. App memindahkannya ke daftar aktif pertama di ruang itu, lalu mengirim ulang.

### Tugas berulang (v1.0)

Tugas berulang dibuat ulang oleh server: begitu satu kejadian diselesaikan, server membuat tugas berikutnya dengan tenggat baru. Aturannya disimpan di tugas itu sendiri lewat `repeat_frequency` dan `repeat_interval`, dengan `due_date` sebagai patokan.

- Tugas berikutnya dibuat server, sekali saja, saat tugas berulang menjadi selesai: `completed_at` berubah dari kosong menjadi terisi, lewat `PATCH` atau `PUT`. Server mengunci baris tugasnya, jadi dua HP yang mencentang tugas yang sama tidak membuat dua tugas berikutnya. Saat offline, tugas berikutnya baru muncul setelah centangnya terkirim.
- Tenggat berikutnya adalah kejadian pertama sesudah tanggal yang lebih akhir antara `due_date` tugas itu dan tanggal selesainya, menurut zona waktu ruang. Kejadian yang terlewat tidak menumpuk: tugas harian bertenggat 20 Oktober yang baru selesai 24 Oktober berikutnya bertenggat 25 Oktober.
- Kejadian dihitung dari tanggal patokan, yaitu `due_date` terakhir yang dipilih pengguna. Tanggal yang tidak ada di bulan yang lebih pendek jatuh ke hari terakhir bulan itu, lalu kembali ke tanggal aslinya: 31 Januari, 28 Februari, 31 Maret. 29 Februari jatuh ke 28 Februari di tahun biasa.
- Mengubah `due_date`, `repeat_frequency`, atau `repeat_interval` menjadikan `due_date` saat itu patokan baru.
- Tugas berikutnya masuk ke daftar yang sama dan membawa judul, catatan, prioritas, penanggung jawab, `due_time`, `position`, dan aturan ulangnya. `created_by` tetap pembuat aslinya, `updated_by` diisi orang yang menyelesaikan, dan `remind_at` dihitung seperti biasa. Tugas berikutnya tidak memicu push `task_assigned`.
- Balasan `PUT` dan `PATCH /tasks/{id}` berbentuk `{"data": {...}, "next_task": {...}}`. `next_task` adalah tugas berikutnya kalau ada dan belum dihapus, dalam bentuk sumber tugas, atau `null`. App langsung menampilkannya tanpa menunggu sync.
- Urungkan setelah centangnya terkirim: app mengirim `completed_at: null`. Kalau tugas berikutnya belum disentuh siapa pun (belum diubah, belum selesai, dan sejak v1.x belum diberi komentar atau reaksi), server ikut menghapusnya dan mengosongkan `next_task_id`, jadi centang ulang membuat tugas berikutnya yang baru. Kalau sudah disentuh, tugas berikutnya dibiarkan, dan centang ulang tidak membuat yang kedua.
- Pengulangan berhenti kalau `repeat_frequency` dikosongkan, atau tugas yang belum selesai dihapus. Tugas yang sudah selesai tetap ada sebagai riwayat, dan mengubah aturan ulangnya tidak membuat apa-apa lagi.
- Mengosongkan `due_date` ikut mengosongkan `repeat_frequency` dan `repeat_interval`. Mengisi `repeat_frequency` di tugas tanpa `due_date`, dicek bersama yang sudah tersimpan, ditolak 422.
- Checklist perjalanan tidak bisa berulang: aturan ulang di daftar dengan `trip_id` ditolak 422, termasuk saat tugas berulang dipindah ke checklist.
- Kolom baru di sumber tugas berarti app versi baru menjalankan sync penuh sekali, seperti saat ada koleksi baru (lihat Aturan di HP).

## Tabungan

Progres dihitung di HP dari daftar setoran: total adalah jumlah setoran dikurangi jumlah penarikan (v1.x). Server menghitung total hanya untuk menandai target tercapai dan mengirim notifikasi.

### Sumber target

```json
{
  "id": "01m4djq1w0ystkpbz7mqd22k3n",
  "space_id": "01m4dgdt40te98hy85v3fe2t18",
  "name": "Dana Liburan",
  "emoji": "✈️",
  "target_amount": 10000000,
  "currency": "IDR",
  "target_date": "2027-06-30",
  "reminder_frequency": "weekly",
  "reminder_day": 5,
  "achieved_at": null,
  "archived_at": null,
  "created_by": "01m4dg5jegv6sp4xav8efqzcqe",
  "created_at": "2026-10-08T11:00:00.000000Z",
  "updated_at": "2026-10-08T11:00:00.000000Z",
  "deleted_at": null
}
```

| Kolom | Saat PUT | Aturan |
| --- | --- | --- |
| `space_id` | wajib | Tidak bisa diubah |
| `name` | wajib | 1–50 karakter |
| `emoji` | opsional | Paling banyak 16 code point; app memastikan isinya satu emoji |
| `target_amount` | wajib | Bilangan bulat lebih dari 0, paling besar 10^15 |
| `currency` | wajib | Kode di tabel Uang; app mengisinya dari `default_currency`. Hanya bisa diisi saat target dibuat |
| `target_date` | opsional | Dipakai untuk menghitung setoran per minggu yang dibutuhkan (FR-14) |
| `archived_at` | – | Hanya lewat PATCH |
| `reminder_frequency` | opsional | `weekly`, `monthly`, atau `null` (tanpa pengingat, bawaan) (v0.2) |
| `reminder_day` | opsional | Wajib kalau `reminder_frequency` terisi: 1–7 untuk mingguan (1 = Senin, 7 = Minggu), 1–31 untuk bulanan (v0.2); di bulan yang lebih pendek, jatuh ke hari terakhir bulan itu |

### Sumber setoran

```json
{
  "id": "01m4dk06v0p3ew0dm0dk94s1k0",
  "savings_goal_id": "01m4djq1w0ystkpbz7mqd22k3n",
  "space_id": "01m4dgdt40te98hy85v3fe2t18",
  "user_id": "01m4dg5jegv6sp4xav8efqzcqe",
  "type": "deposit",
  "amount": 200000,
  "entry_date": "2026-10-08",
  "note": "Gajian",
  "created_at": "2026-10-08T11:05:00.000000Z",
  "updated_at": "2026-10-08T11:05:00.000000Z",
  "deleted_at": null
}
```

| Kolom | Saat PUT | Aturan |
| --- | --- | --- |
| `savings_goal_id` | wajib | Target yang belum dihapus; tidak bisa diubah. `space_id` ikut dari targetnya |
| `amount` | wajib | Bilangan bulat lebih dari 0, paling besar 10^15, dalam mata uang targetnya |
| `entry_date` | wajib | Tanggal setor menurut HP; tidak diisi server karena setoran bisa terkirim belakangan |
| `note` | opsional | Paling banyak 200 karakter |
| `type` | opsional | `deposit` (bawaan) atau `withdrawal`. Hanya diisi saat PUT; PATCH mengabaikannya (v1.x) |

- `user_id` diisi server dengan akun yang mencatat. `type` membedakan setoran dan penarikan (v1.x), dan di v0.1 selalu `deposit`.
- Kedua anggota boleh mengubah dan menghapus setoran siapa pun.
- Setiap kali setoran dibuat, diubah, atau dihapus, atau `target_amount` berubah, server menghitung ulang total dalam transaksi yang mengunci baris target. Total mencapai target: `achieved_at` diisi. Total turun lagi di bawah target: `achieved_at` kembali `null`.
- Push `savings_achieved` dikirim paling banyak sekali per target, dicatat di `achieved_notified_at`. Kalau push itu terkirim karena sebuah setoran, push `savings_deposit` untuk setoran yang sama tidak dikirim.
- `DELETE /savings-goals/{id}` ikut menghapus semua setorannya dan memperbarui `updated_at` setoran itu. Setoran ke target yang sudah dihapus dibalas 409 `parent_deleted`; setoran ke target yang diarsipkan tetap diterima.

### Penarikan tabungan (v1.x)

Penarikan dicatat sebagai setoran dengan `type` `withdrawal`, jadi tidak ada endpoint atau koleksi baru. `amount` tetap bilangan bulat lebih dari 0; arah uangnya ditentukan `type`.

- `type` hanya diisi saat `PUT`: `deposit` (bawaan) atau `withdrawal`. `PATCH` mengabaikannya, jadi setoran tidak bisa diubah menjadi penarikan atau sebaliknya. Nilai lain ditolak 422.
- Total target = jumlah setoran dikurangi jumlah penarikan. Total ini yang dipakai untuk `achieved_at`, persen di push `savings_reminder`, dan progres di app. Kalau penarikan membuat total turun di bawah target, `achieved_at` kembali `null`. Push `savings_achieved` tetap hanya sekali per target, jadi tidak terkirim lagi saat target tercapai kembali.
- Penarikan yang melebihi saldo tetap diterima, karena dua HP bisa mencatat saat offline bersamaan dan 422 dari antrean harus dihindari. App yang mencegahnya di layar, dan total boleh negatif.
- Push `savings_withdrawal` dikirim ke anggota lain saat penarikan baru dicatat, dengan preferensi `savings_activity`, channel `aktivitas`, `visibility` `PRIVATE`, dan `tag` `goal-{id}` seperti push tabungan lain. Judulnya nama target, isinya "{nama} mengambil {nominal}". Mengubah atau menghapus penarikan tidak mengirim push, dan penarikan tidak pernah memicu `savings_deposit`.
- Kolom `type` sudah ada sejak v0.1, jadi app tidak perlu sync penuh. App versi lama menghitung penarikan sebagai setoran, jadi kedua HP perlu memakai app baru sebelum penarikan pertama dicatat.

### Pengingat setor rutin (v0.2)

- Jadwalnya disimpan di target: `reminder_frequency` (`weekly` atau `monthly`) dan `reminder_day`. Tanpa `reminder_frequency`, target tidak diingatkan.
- Push `savings_reminder` dikirim pukul 08.00 di zona waktu ruangnya: tiap minggu pada hari `reminder_day` (1 = Senin, 7 = Minggu), atau tiap bulan pada tanggal `reminder_day`. Tanggal bulanan boleh 1–31. Kalau bulan itu lebih pendek (mis. tanggal 31 di April, atau 29–31 di Februari), push dikirim di hari terakhir bulan itu.
- Mengosongkan `reminder_frequency` ikut mengosongkan `reminder_day`. Mengubah salah satunya lewat PATCH dicek bersama yang sudah tersimpan.
- Penerima: kedua anggota untuk target di Ruang Bersama, pemilik ruang untuk target di Ruang Pribadi.
- Target yang sudah tercapai, diarsipkan, atau dihapus tidak diingatkan. Setoran yang sudah masuk minggu atau bulan itu tidak membatalkan pengingat.
- Hanya dikirim kalau `notification_prefs.savings_reminders` penerima menyala. Kunci ini baru di v0.2 dan bawaannya menyala.
- Judul push adalah nama target, isinya "Waktunya menabung. Terkumpul {persen}% dari target." Persen dihitung dari total bersih, dibulatkan ke bawah, dan paling kecil 0 (v1.x).
- Paling banyak sekali per target per hari. Yang telat lebih dari 30 menit dilewati.

## Tanggal penting (v0.2)

Tanggal penting adalah acara tahunan: anniversary, ulang tahun orang lain, atau tanggal lain yang kalian pilih. Ulang tahun kalian berdua tidak dicatat di sini, tetapi diambil dari `birth_date` di profil.

### Sumber tanggal penting

```json
{
  "id": "01m4dm2k70z5bq7rn3xw8c4v1e",
  "space_id": "01m4dgdt40te98hy85v3fe2t18",
  "type": "anniversary",
  "title": "Hari jadi kita",
  "date": "2023-10-29",
  "created_by": "01m4dg5jegv6sp4xav8efqzcqe",
  "created_at": "2026-10-22T09:00:00.000000Z",
  "updated_at": "2026-10-22T09:00:00.000000Z",
  "deleted_at": null
}
```

| Kolom | Saat PUT | Aturan |
| --- | --- | --- |
| `space_id` | wajib | Ruang tempatmu menjadi anggota; tidak bisa diubah |
| `type` | wajib | `birthday`, `anniversary`, atau `other` (Lainnya) |
| `title` | wajib | 1–50 karakter, misalnya "Hari jadi kita" atau "Ulang tahun Ibu" |
| `date` | wajib | `YYYY-MM-DD`, atau `--MM-DD` kalau tahunnya tidak diketahui; dicek dengan pola dan kalender seperti `birth_date` (`--02-30` ditolak, `--02-29` boleh) |

- Endpoint: `PUT`, `PATCH`, dan `DELETE /special-dates/{id}`, dengan aturan tulis yang sama seperti sumber lain. `PUT` hanya membuat, `PATCH` hanya mengubah kolom yang dikirim, dan `DELETE` menghapus lunak (204).
- `created_by` diisi server. Kedua anggota Ruang Bersama boleh mengubah dan menghapus tanggal penting siapa pun.
- Tanggal di Ruang Pribadi tidak pernah terlihat pasangan, jadi bisa dipakai untuk kejutan.
- Hitung mundur di Beranda dan kartu ucapan hari H dihitung di HP dari hasil sync. Angka seperti "ke-3" hanya tampil kalau tahunnya diisi.
- 29 Februari diingatkan dan dirayakan pada 28 Februari di tahun biasa.

### Ulang tahun anggota

- Ulang tahun kalian berdua diambil dari `birth_date` di profil. Setiap anggota di sumber ruang ikut membawa `birth_date`, supaya HP pasangan bisa menghitung mundur.
- Pengingatnya hanya dikirim ke pasangan, memakai zona waktu Ruang Bersama. Akun yang belum punya Ruang Bersama, atau yang `birth_date`-nya kosong, tidak diingatkan.

### Pengingat tanggal penting

- Push `special_date_reminder` dikirim H-7, H-1, dan hari H pukul 08.00 di zona waktu ruangnya. Jadwal ini tetap di v0.2 dan tidak bisa diatur.
- Penerima: kedua anggota untuk tanggal di Ruang Bersama, pemilik ruang untuk tanggal di Ruang Pribadi, dan hanya pasangan untuk ulang tahun anggota.
- Hanya dikirim kalau `notification_prefs.special_dates` penerima menyala. Kunci ini baru di v0.2 dan bawaannya menyala.
- Judul push "Tanggal penting", isinya "{judul} 7 hari lagi", "{judul} besok", atau "{judul} hari ini". Untuk ulang tahun anggota, `{judul}` berbunyi "Ulang tahun {nama}".
- Setiap pengingat dikirim paling banyak sekali per tanggal per tahun. Yang telat lebih dari 30 menit dilewati, sama seperti pengingat tugas, termasuk saat tanggalnya baru dibuat atau diubah setelah pukul 08.00.

## Perjalanan dan checklist persiapan (v0.2)

Perjalanan menyimpan rencana liburan: judul, tujuan, tanggal, catatan, dan tautan ke target tabungan. Checklist persiapannya adalah daftar tugas biasa yang ditandai `trip_id`, jadi itemnya tugas biasa yang ikut muncul di Hari Ini kalau bertenggat.

### Sumber perjalanan

```json
{
  "id": "01m4dp5r20vbx9k3ws7ty6hn2q",
  "space_id": "01m4dgdt40te98hy85v3fe2t18",
  "title": "Liburan ke Bali",
  "destination": "Bali",
  "start_date": "2027-06-20",
  "end_date": "2027-06-25",
  "notes": null,
  "savings_goal_id": "01m4djq1w0ystkpbz7mqd22k3n",
  "created_by": "01m4dg5jegv6sp4xav8efqzcqe",
  "created_at": "2026-10-22T10:00:00.000000Z",
  "updated_at": "2026-10-22T10:00:00.000000Z",
  "deleted_at": null
}
```

| Kolom | Saat PUT | Aturan |
| --- | --- | --- |
| `space_id` | wajib | Ruang tempatmu menjadi anggota; tidak bisa diubah |
| `title` | wajib | 1–50 karakter |
| `destination` | opsional | Paling banyak 100 karakter |
| `start_date` | opsional | `YYYY-MM-DD`; kosong berarti tanggalnya belum pasti |
| `end_date` | opsional | `YYYY-MM-DD`; butuh `start_date` dan tidak boleh lebih awal darinya |
| `notes` | opsional | Paling banyak 5.000 karakter |
| `savings_goal_id` | opsional | Target di ruang yang sama; target yang ternyata sudah dihapus disimpan sebagai tanpa tautan, supaya perubahan lainnya tetap masuk; `null` melepas tautan |

- Endpoint: `PUT`, `PATCH`, dan `DELETE /trips/{id}`, dengan aturan tulis yang sama seperti sumber lain.
- `created_by` diisi server. Kedua anggota Ruang Bersama boleh mengubah dan menghapus perjalanan siapa pun.
- Status dihitung di HP dari tanggal lokal: direncanakan kalau `start_date` kosong atau belum tiba, berlangsung dari `start_date` sampai `end_date` (atau hanya di `start_date` kalau `end_date` kosong), dan selesai sesudahnya.
- Satu target tabungan boleh ditautkan ke beberapa perjalanan. Kalau targetnya dihapus, server mengosongkan `savings_goal_id` perjalanan yang menautkannya dan memperbarui `updated_at`-nya.
- `DELETE /trips/{id}` ikut menghapus checklist beserta tugasnya (soft delete) dan memperbarui `updated_at` keduanya.

### Checklist persiapan

- Checklist adalah daftar tugas dengan `trip_id` terisi. App membuatnya sendiri lewat `PUT /lists/{id}`, tepat setelah perjalanannya, supaya bisa langsung diisi walau offline.
- `trip_id` hanya bisa diisi saat daftar dibuat. Perjalanannya harus di ruang yang sama dengan daftar; perjalanan yang sudah dihapus dibalas 409 `parent_deleted`.
- Satu perjalanan paling banyak punya satu checklist. Checklist kedua ditolak 422 di kolom `trip_id`.
- Checklist tidak dihitung sebagai daftar aktif: tidak tampil di daftar biasa, tidak dipakai tambah cepat, dan tidak dihitung untuk `last_list`.
- Checklist tidak bisa dihapus atau diarsipkan sendiri; percobaannya dibalas 409 `trip_checklist`. Checklist ikut terhapus bersama perjalanannya.
- Itemnya tugas biasa: punya penanggung jawab, tenggat, dan pengingat. Progres persiapan (item selesai dibagi total) dihitung di HP.

### Pengingat persiapan

- Push `trip_reminder` dikirim H-7 dan H-1 sebelum `start_date`, pukul 08.00 di zona waktu ruangnya. Perjalanan tanpa `start_date` tidak diingatkan.
- Penerima: kedua anggota untuk perjalanan di Ruang Bersama, pemilik ruang untuk perjalanan di Ruang Pribadi.
- Hanya dikirim kalau `notification_prefs.trip_reminders` penerima menyala. Kunci ini baru di v0.2 dan bawaannya menyala.
- Judul push "Perjalanan", isinya "{judul} 7 hari lagi" atau "{judul} besok". Kalau checklist sudah berisi, ditambah "Persiapan {selesai} dari {total} beres."
- Setiap pengingat dikirim paling banyak sekali per perjalanan per tanggal. Yang telat lebih dari 30 menit dilewati. Kalau `start_date` diubah, pengingat untuk tanggal barunya tetap dikirim.

## Kenangan dan foto (v0.3)

Kenangan menyimpan tempat yang pernah kalian datangi: judul, nama tempat, tanggal, cerita, dan paling banyak 50 foto. Foto diunggah satu per satu dengan ID dari HP, jadi bisa ditambahkan saat offline dan aman dikirim ulang.

### Sumber kenangan

```json
{
  "id": "01m4dr8c50jk2mt7xw3pq9vb6e",
  "space_id": "01m4dgdt40te98hy85v3fe2t18",
  "trip_id": "01m4dp5r20vbx9k3ws7ty6hn2q",
  "title": "Liburan ke Bali",
  "place_name": "Ubud, Bali",
  "latitude": -8.506853,
  "longitude": 115.262477,
  "visited_on": "2027-06-20",
  "story": "Hari pertama hujan, tapi sawahnya tetap hijau.",
  "created_by": "01m4dg5jegv6sp4xav8efqzcqe",
  "created_at": "2027-06-26T09:00:00.000000Z",
  "updated_at": "2027-06-26T09:00:00.000000Z",
  "deleted_at": null
}
```

| Kolom | Saat PUT | Aturan |
| --- | --- | --- |
| `space_id` | wajib | Ruang tempatmu menjadi anggota; tidak bisa diubah |
| `title` | wajib | 1–50 karakter |
| `place_name` | opsional | Nama tempat, paling banyak 100 karakter |
| `visited_on` | wajib | `YYYY-MM-DD`; tanggal kenangan di linimasa |
| `story` | opsional | Paling banyak 10.000 karakter |
| `trip_id` | opsional | Perjalanan di ruang yang sama; perjalanan yang ternyata sudah dihapus disimpan sebagai tanpa tautan; `null` melepas tautan |
| `latitude` | opsional | -90 sampai 90, berpasangan dengan `longitude`; dibulatkan ke 6 desimal (v1.x) |
| `longitude` | opsional | -180 sampai 180, berpasangan dengan `latitude`; dibulatkan ke 6 desimal (v1.x) |

- Endpoint: `PUT`, `PATCH`, dan `DELETE /memories/{id}`, dengan aturan tulis yang sama seperti sumber lain.
- `created_by` diisi server. Kedua anggota Ruang Bersama boleh mengubah dan menghapus kenangan dan foto siapa pun.
- Linimasa dikelompokkan per tahun dan bulan dari `visited_on`, dihitung di HP dari hasil sync. Foto sampul adalah foto dengan `position` terkecil; kenangan tanpa foto tampil tanpa sampul.
- "Simpan jadi kenangan" (UC-17) tampil di kartu perjalanan yang sudah selesai. App mengisi `title` dari judul perjalanan, `place_name` dari tujuannya, `visited_on` dari `start_date`, dan `trip_id`, lalu membuat kenangan dengan `PUT` biasa. Satu perjalanan boleh punya beberapa kenangan.
- Kalau perjalanannya dihapus, server mengosongkan `trip_id` kenangannya dan memperbarui `updated_at`-nya; kenangannya tetap ada.
- `DELETE /memories/{id}` ikut menghapus semua fotonya (soft delete) dan memperbarui `updated_at` foto itu. Kalau kenangannya berfoto, app meminta konfirmasi dulu.

### Lokasi dan cari tempat (v1.x)

Kenangan bisa diberi pin lokasi lewat `latitude` dan `longitude`, dan Peta kenangan disusun app dari hasil sync. Untuk mencari tempat, app memanggil `GET /places`; server yang meneruskannya ke Nominatim (OpenStreetMap), jadi HP tidak menghubungi pihak ketiga selain ubin peta.

- `latitude` (-90 sampai 90) dan `longitude` (-180 sampai 180) selalu berpasangan: kirim keduanya untuk memasang pin, keduanya `null` untuk melepasnya. Hanya satu yang terisi, atau `PATCH` yang mengirim salah satunya saja, ditolak 422.
- Server membulatkan keduanya ke 6 angka desimal (sekitar 11 cm) dan mengirimnya sebagai angka JSON; di Dart baca sebagai `num`.
- Kolom baru di sumber kenangan berarti app versi baru menjalankan sync penuh sekali.

`GET /places?q=ubud` dipanggil langsung, tidak lewat antrean, jadi butuh koneksi:

```json
{
  "data": [
    {
      "name": "Ubud",
      "address": "Ubud, Gianyar, Bali, Indonesia",
      "latitude": -8.506853,
      "longitude": 115.262477
    }
  ]
}
```

- `q` 3–100 karakter. Paling banyak 8 hasil, urut seperti dari Nominatim; tanpa hasil, `data` kosong. Nama dan alamat dalam bahasa Indonesia kalau tersedia.
- App mencari hanya saat pengguna menekan tombol cari, bukan setiap kali mengetik: kebijakan Nominatim melarang autocomplete, termasuk dari sisi app.
- Hasil disimpan di server 1 hari per kata kunci (huruf besar-kecil dan spasi berlebih tidak dibedakan), jadi pencarian yang sama tidak sampai ke Nominatim lagi.
- Server mengirim paling banyak 1 request per detik ke Nominatim untuk seluruh app, dengan User-Agent Denly Days, dan hanya meneruskan kata kuncinya tanpa data akun. Request yang tidak mendapat giliran dalam 2 detik dibalas 429 `too_many_requests`.
- Batasnya 20 pencarian per menit per akun.
- Kalau Nominatim gagal atau pencarian dimatikan di server, balasannya 503 `places_unavailable`. App menampilkan pesannya, dan pin tetap bisa dipasang langsung di peta. Saklar `PLACES_SEARCH_ENABLED` ada karena kebijakan Nominatim mewajibkan app bisa berhenti memakai layanannya kapan pun OSMF meminta, tanpa update app.
- App menampilkan "© OpenStreetMap contributors" di bawah hasil pencarian dan di peta. Ubin peta diambil app langsung dan mengikuti kebijakan tile OSM.

### Foto kenangan

```json
{
  "id": "01m4drb7k0qz3n8st5vw2hx4cy",
  "memory_id": "01m4dr8c50jk2mt7xw3pq9vb6e",
  "space_id": "01m4dgdt40te98hy85v3fe2t18",
  "uploaded_by": "01m4dg5jegv6sp4xav8efqzcqe",
  "position": 1024,
  "width": 2560,
  "height": 1920,
  "taken_at": "2027-06-20T03:12:45.000000Z",
  "url": "https://….r2.cloudflarestorage.com/…?X-Amz-Expires=604800&…",
  "thumb_url": "https://….r2.cloudflarestorage.com/…?X-Amz-Expires=604800&…",
  "created_at": "2027-06-26T09:00:05.000000Z",
  "updated_at": "2027-06-26T09:00:05.000000Z",
  "deleted_at": null
}
```

Foto diunggah lewat `POST /memory-photos/{id}` dengan `multipart/form-data`:

| Kolom | Saat unggah | Aturan |
| --- | --- | --- |
| `memory_id` | wajib | Kenangan di ruangmu yang belum dihapus; tidak bisa diubah. `space_id` ikut dari kenangannya |
| `photo` | wajib | JPEG, PNG, atau WebP, paling besar 10 MB dan 4096 px per sisi |
| `position` | wajib | Angka, boleh desimal seperti `position` daftar; yang terkecil jadi sampul |
| `taken_at` | opsional | Waktu foto diambil menurut HP, dengan `Z` atau offset |

- Balasannya mengikuti aturan `PUT`: 201 kalau baru; 200 dengan data tersimpan kalau ID itu sudah ada di ruang yang sama, dan file kiriman ulangnya diabaikan; 410 kalau foto itu sudah dihapus; 409 `id_conflict` kalau ID dipakai di ruang lain. Methodnya `POST` karena PHP tidak membaca multipart pada `PUT`.
- Foto ke kenangan yang sudah dihapus dibalas 409 `parent_deleted`. Foto ke-51 di satu kenangan ditolak 422 di kolom `photo`.
- App mengecilkan foto ke sisi terpanjang 2560 px dan mengompresnya sebagai JPEG sebelum unggah, jadi biasanya di bawah 1,5 MB. `taken_at` dibaca dari EXIF file asli sebelum dikompres.
- Server menyimpan ulang dua versi JPEG tanpa metadata, sehingga lokasi di EXIF terbuang: versi penuh dengan sisi terpanjang paling besar 2560 px, dan thumbnail 800 px untuk linimasa dan galeri. Foto yang lebih kecil tidak diperbesar. `width` dan `height` adalah ukuran versi penuh, dan `uploaded_by` diisi server.
- `PATCH /memory-photos/{id}` hanya mengubah `position`, misalnya untuk "Jadikan sampul" (lebih kecil dari foto pertama).
- `DELETE /memory-photos/{id}` → 204. App memakai jeda Urungkan 5 detik seperti hapus tugas.
- File foto yang dihapus, termasuk karena kenangannya dihapus, ikut dihapus dari R2 lewat job antrean dan tidak bisa dipulihkan.

### URL foto

- `url` (versi penuh) dan `thumb_url` adalah URL R2 bertanda tangan yang berlaku 7 hari, seperti `avatar_url`. Foto yang sudah dihapus membawa `null`.
- App menyimpan gambar di cache dengan kunci `photo-{id}` dan `photo-{id}-thumb`, bukan URL-nya. Thumbnail diunduh tepat setelah sync supaya linimasa dan galeri tetap tampil saat offline; versi penuh diunduh saat foto dibuka.
- Kalau URL sudah berumur 7 hari atau R2 membalas 403, app mengambil URL baru lewat `GET /memories/{id}/photos`. Balasannya semua foto kenangan itu yang belum dihapus, urut `position`, dalam bentuk sumber foto di atas. Kenangan yang sudah dihapus dibalas 410, dan yang bukan di ruangmu 404.
- Foto yang diunggah dari HP ini langsung dipakai sebagai cache versi penuhnya, jadi tidak diunduh lagi.

### Notifikasi kenangan baru

- Push `memory_created` dikirim ke anggota lain saat kenangan baru dibuat di Ruang Bersama, termasuk lewat "Simpan jadi kenangan". Menambah foto ke kenangan lama tidak mengirim push.
- Push dikirim 2 menit setelah kenangan dibuat, supaya fotonya sempat terunggah, dan batal kalau kenangannya sudah dihapus.
- Hanya dikirim kalau `notification_prefs.partner_activity` penerima menyala, tanpa kunci baru.
- Judul push adalah judul kenangan, isinya "{nama} menyimpan kenangan baru".

## Reaksi dan komentar (v1.x)

Tugas, setoran, dan kenangan di Ruang Bersama bisa diberi komentar dan reaksi emoji. Keduanya memakai ID dari HP dan lewat antrean, jadi bisa dibuat saat offline dan aman dikirim ulang.

### Sumber komentar

```json
{
  "id": "01m4dt2k70f3qzr8vw5hx9cy4b",
  "space_id": "01m4dgdt40te98hy85v3fe2t18",
  "subject_type": "memory",
  "subject_id": "01m4dr8c50jk2mt7xw3pq9vb6e",
  "user_id": "01m4dga4y03c5ratqsc31zc916",
  "body": "Sawahnya masih hijau banget!",
  "created_at": "2027-06-26T09:05:00.000000Z",
  "updated_at": "2027-06-26T09:05:00.000000Z",
  "deleted_at": null
}
```

| Kolom | Saat PUT | Aturan |
| --- | --- | --- |
| `subject_type` | wajib | `task`, `savings_entry` (setoran atau penarikan), atau `memory`; tidak bisa diubah |
| `subject_id` | wajib | Tugas, setoran, atau kenangan di Ruang Bersama yang belum dihapus; tidak bisa diubah. `space_id` ikut dari subjeknya |
| `body` | wajib | 1–1.000 karakter; satu-satunya kolom yang bisa diubah lewat `PATCH` |

- Endpoint: `PUT`, `PATCH`, dan `DELETE /comments/{id}`, dengan aturan tulis yang sama seperti sumber lain.
- `user_id` diisi server dengan penulisnya. Hanya penulis yang boleh mengubah dan menghapus komentarnya; yang lain dibalas 403 `forbidden` dengan `current`.
- App mengurutkan komentar menurut `created_at`. Komentar yang `updated_at`-nya lebih baru dari `created_at` sudah diubah, jadi app boleh menandainya "diedit".

### Sumber reaksi

```json
{
  "id": "01m4dt3f80g7mnp2xs6ty8vk1d",
  "space_id": "01m4dgdt40te98hy85v3fe2t18",
  "subject_type": "memory",
  "subject_id": "01m4dr8c50jk2mt7xw3pq9vb6e",
  "user_id": "01m4dg5jegv6sp4xav8efqzcqe",
  "emoji": "❤️",
  "created_at": "2027-06-26T09:06:00.000000Z",
  "updated_at": "2027-06-26T09:06:00.000000Z",
  "deleted_at": null
}
```

| Kolom | Saat PUT | Aturan |
| --- | --- | --- |
| `subject_type` | wajib | Sama seperti komentar |
| `subject_id` | wajib | Sama seperti komentar |
| `emoji` | wajib | 1–16 code point; app memastikan isinya satu emoji |

- Endpoint: `PUT` dan `DELETE /reactions/{id}`. Reaksi tidak punya `PATCH`: mengganti emoji berarti reaksi baru dengan ID baru.
- Satu reaksi per orang per subjek. `PUT` reaksi baru untuk subjek yang sudah punya reaksimu menghapus reaksi lama (soft delete, `updated_at` diperbarui), lalu menyimpan yang baru (201). App cukup mengirim `PUT` itu dan menghapus reaksi lama di cache saat itu juga.
- Server mengunci baris subjeknya saat menyimpan, jadi dua reaksi yang tiba bersamaan tetap menyisakan satu.
- Hanya pemilik reaksi yang boleh menghapusnya; yang lain dibalas 403 `forbidden` dengan `current`.

### Aturan komentar dan reaksi

- Hanya di Ruang Bersama: subjek di Ruang Pribadi ditolak 422 di kolom `subject_id`.
- Subjek yang tidak ada atau bukan di ruangmu dibalas 404. Subjek yang sudah dihapus dibalas 409 `parent_deleted`, dan app membuang komentar atau reaksi itu dari antrean. Subjek di daftar atau target yang diarsipkan tetap bisa dikomentari dan diberi reaksi.
- Menghapus subjek ikut menghapus komentar dan reaksinya (soft delete, `updated_at` diperbarui), termasuk subjek yang terhapus berantai: daftar ke tugasnya, perjalanan ke tugas checklistnya, dan target ke setorannya.
- Server mengunci baris subjek saat menyimpan komentar atau reaksi, jadi tidak ada yang tertinggal di subjek yang sedang dihapus.
- Tugas berikutnya dari tugas berulang yang sudah diberi komentar atau reaksi dianggap sudah disentuh, jadi tidak ikut terhapus saat centang tugas sebelumnya diurungkan.
- Koleksi sync baru `comments` dan `reactions`, jadi `cursor` lama ditolak sekali dan app versi baru menjalankan sync penuh sekali.

### Notifikasi komentar dan reaksi

| `type` | Pemicu | Isi |
| --- | --- | --- |
| `comment_created` | Komentar baru dibuat | "{nama}: {komentar}"; spasi dan baris baru dirapikan, dan komentar yang lebih dari 80 karakter dipotong dengan "…" |
| `reaction_created` | Reaksi baru disimpan; paling banyak satu per subjek per 10 menit untuk tiap penerima | "{nama} memberi reaksi {emoji}" |

- Penerima: anggota lain di Ruang Bersama itu, dengan preferensi `partner_activity`, channel `aktivitas`, dan prioritas normal.
- Judulnya judul tugas atau kenangan, atau nama target untuk setoran.
- `data` membawa `subject_type` dan `subject_id`, ditambah `savings_goal_id` untuk setoran. `tag`-nya `comment-{subject_id}` atau `reaction-{subject_id}`, jadi notifikasi baru untuk subjek yang sama menimpa yang lama.
- Push untuk setoran memakai `visibility` `PRIVATE`, seperti push tabungan lain.
- Mengubah atau menghapus komentar tidak mengirim push. Reaksi yang diganti atau dihapus dalam 10 menit itu juga tidak mengirim push lagi.
- Saat diketuk, app membuka detail subjeknya beserta komentarnya, mengikuti aturan ketuk di bagian Notifikasi push.

## Sinkron

`GET /sync` adalah satu-satunya cara HP membaca data ruang: sekali penuh setelah masuk, lalu hanya yang berubah. Beranda, filter tugas, dan statistik tabungan dihitung dari hasilnya di cache lokal.

Alur satu perubahan dari HP kamu ke HP pasangan (contoh: kamu menyelesaikan tugas titipan pasangan):

1. **HP kamu:** kamu mencentang tugas; layar langsung berubah dan PATCH disimpan dulu di antrean.
2. **HP kamu ke server:** antrean mengirim `PATCH /tasks/{id}` berisi `completed_at`; kalau gagal, dicoba lagi otomatis.
3. **Server:** menyimpan ke MySQL dengan `updated_at` baru, membalas 200, dan item antrean selesai.
4. **Server ke FCM:** job antrean mengirim push `task_completed`.
5. **FCM ke HP pasangan:** notifikasi muncul walau app pasangan sedang tertutup.
6. **HP pasangan ke server:** pasangan membuka app, lalu `GET /sync` dengan kursor terakhir.
7. **Server ke HP pasangan:** semua yang berubah sejak kursor dikirim, dan cache pasangan diperbarui.

Langkah 1 tidak menunggu server, dan langkah 2 diulang sampai berhasil, jadi centang tidak hilang walau sinyal putus.

### Request dan balasan

Halaman pertama: `GET /sync?since=<server_time tersimpan>`, atau tanpa `since` untuk sync penuh. Halaman berikutnya: `GET /sync?cursor=<next_cursor>`. Setiap daftar di `data` berisi sumber yang sama seperti di bagian masing-masing, termasuk yang terhapus (`deleted_at` terisi).

```json
{
  "data": {
    "spaces": [],
    "task_lists": [],
    "tasks": [],
    "savings_goals": [],
    "savings_entries": [],
    "special_dates": [],
    "trips": [],
    "memories": [],
    "memory_photos": [],
    "comments": [],
    "reactions": []
  },
  "meta": {
    "server_time": "2026-10-08T11:10:05.123456Z",
    "has_more": false,
    "next_cursor": null
  }
}
```

### Aturan di server

- `server_time` dicatat saat halaman pertama diproses dan sama di semua halaman. App menyimpannya setelah halaman terakhir sebagai `since` berikutnya.
- Server mundur 5 detik dari `since`, sekali saja di halaman pertama, supaya perubahan yang sedang disimpan tidak terlewat.
- Setiap daftar diurutkan `(updated_at, id)` dan dipotong paling banyak 500 baris. `next_cursor` menyimpan posisi terakhir tiap daftar; app cukup mengirimnya balik apa adanya. Dengan begitu baris yang `updated_at`-nya sama tidak terulang atau terlewat.
- Tanpa `since`: semua data aktif di semua ruangmu, tanpa yang terhapus. Hanya dipakai saat database lokal akun itu masih kosong, yaitu setelah daftar atau saat akun itu pertama kali masuk di HP tersebut.
- Isi Ruang Pribadi pasangan tidak pernah ikut terkirim.
- Ruang Bersama yang ditutup (v1.0) tiba di `spaces` dengan `deleted_at` terisi, tetapi isinya tidak dikirim lagi. Ruang yang dipulihkan tiba lagi dengan `deleted_at` kosong, dan semua isinya ikut terkirim lewat `since`, karena server memperbarui `updated_at`-nya saat memulihkan.
- `cursor` yang bukan buatan server dibalas 422 `validation_failed`. App lalu mengulang sync dari `since` yang tersimpan.
- Kalau `cursor` dikirim, `since` tidak dibaca.
- Saat koleksi baru ditambahkan (v0.2: `special_dates`, lalu `trips`; v0.3: `memories` dan `memory_photos`; v1.x: `comments` dan `reactions`), `cursor` yang dibuat sebelum deploy ditolak sekali dengan 422, lalu app mengulang dari `since`.

### Aturan di HP

- Hanya satu sync berjalan pada satu waktu. Sebelum menarik data, app mengirim operasi antrean yang bisa dikirim saat itu, lalu tetap menarik walau masih ada yang menunggu jeda Urungkan atau coba ulang.
- Kalau database lokal akun itu sudah punya kursor, misalnya saat masuk lagi setelah keluar, app melanjutkan dengan `since`. Dengan begitu data yang dihapus pasangan selama itu ikut terhapus di HP.
- Baris dari server ditimpa berdasarkan `id`, tanpa pengecekan foreign key selama sync, karena tugas bisa tiba sebelum daftarnya.
- Data yang masih punya operasi di antrean dilindungi: kolomnya tidak ditimpa, dan baris yang DELETE-nya masih di antrean tidak muncul lagi.
- Baris yang `updated_at`-nya lebih tua dari salinan lokal diabaikan.
- Saat induk tiba dengan `deleted_at` terisi, app ikut menghapus anak-anaknya di cache, kecuali anak yang masih punya operasi di antrean; nasibnya ditentukan balasan server (`parent_deleted`).
- Ruang adalah induk dari semua isinya (v1.0). Saat Ruang Bersama tiba dengan `deleted_at` terisi, app menghapus ruang itu beserta semua daftar, tugas, target, setoran, tanggal penting, perjalanan, kenangan, foto, komentar, dan reaksinya dari cache, dengan pengecualian yang sama.
- Sync penuh hanya menambah dan memperbarui.
- Sync berjalan saat app dibuka atau kembali aktif, saat layar ditarik untuk menyegarkan, saat push masuk ketika app terbuka, termasuk push sinkron (v1.x), dan tiap 30 detik selama app tampil (v1.x).
- App versi baru yang mengenal koleksi atau kolom baru (v0.2: `special_dates`, lalu `trips`; v0.3: `memories` dan `memory_photos`; v1.0: aturan ulang di `tasks`; v1.x: lokasi di `memories`, lalu `comments` dan `reactions`) menjalankan sync penuh sekali sebelum kembali memakai `since`. Tanpa itu, data yang dibuat atau diubah pasangan selama app masih versi lama tidak pernah sampai lengkap, karena `since` sudah melewatinya.

### Antrean di HP

- Isi antrean dikirim berurutan, satu per satu. Setiap operasi ditandai "pernah dikirim" sebelum dikirim.
- Perubahan baru hanya boleh digabung ke operasi untuk data yang sama kalau operasi itu yang paling akhir di antrean, belum pernah dikirim, dan tidak sedang menunggu Urungkan. Selain itu, perubahan masuk sebagai operasi baru di ujung antrean. Nilai "sebelum diubah" yang disimpan adalah yang paling awal.
- Kalau `PUT` dibalas 200 (sudah tersimpan dari kiriman sebelumnya), balasannya diterapkan seperti baris sync, dengan kolom di antrean tetap dilindungi. Tidak ada operasi tambahan.
- Selesai dan hapus tugas masuk antrean sebagai operasi tersendiri dengan jeda kirim 5 detik selama tombol Urungkan tampil (FR-08). Urungkan hanya menghapus operasi itu, jadi pasangan tidak menerima notifikasi yang dibatalkan.
- Di layar detail, perubahan dikirim 1 detik setelah berhenti mengetik, dan langsung ditulis ke antrean saat app masuk ke latar belakang.
- Unggah foto kenangan (v0.3) masuk antrean setelah `PUT` kenangannya. File yang sudah dikompres disimpan di HP sampai server membalas, lalu dipakai sebagai cache versi penuhnya.

### Push sinkron (v1.x)

Push `sync` memberi tahu HP pasangan bahwa ada data baru di Ruang Bersama, supaya app yang sedang terbuka langsung menjalankan sync tanpa menunggu putaran 30 detik berikutnya. Pesannya hanya berisi data, tanpa judul dan isi, jadi tidak pernah tampil sebagai notifikasi.

```json
{
  "data": {
    "type": "sync",
    "space_id": "01m4dgdt40te98hy85v3fe2t18"
  },
  "android": {
    "priority": "normal",
    "collapse_key": "sync-01m4dgdt40te98hy85v3fe2t18"
  }
}
```

- Pemicu: setiap perubahan di Ruang Bersama yang ikut terkirim lewat `GET /sync`, yaitu ruang dan anggotanya, daftar, tugas, target, setoran, tanggal penting, perjalanan, kenangan, foto kenangan, komentar, dan reaksi (v1.x). Penghapusan ikut dihitung, begitu juga perubahan yang dibuat server dalam request yang sama, seperti tugas berikutnya dari tugas berulang. Perubahan di Ruang Pribadi tidak memicunya.
- Penerima: anggota lain di ruang itu, di semua perangkatnya. Pelaku tidak menerimanya, termasuk di HP lain miliknya.
- Selalu dikirim, tanpa melihat `notification_prefs`, karena tidak tampil di layar.
- Paling banyak satu push per penerima setiap 10 detik. Perubahan pertama dikirim langsung dan biasanya sampai dalam beberapa detik. Perubahan lain selama jeda 10 detik itu digabung menjadi satu push di akhir jeda, jadi antrean HP yang terkirim beruntun tidak membanjiri HP pasangan.
- Push ini hanya mempercepat. FCM tidak menjamin setiap pesan sampai, jadi app tetap sync saat dibuka, saat kembali aktif, dan tiap 30 detik selama tampil.
- Sesekali push bisa datang tanpa ada yang baru, misalnya saat penyimpanan dibatalkan di tengah jalan. App cukup menjalankan sync seperti biasa.
- Tidak ada kolom atau koleksi baru, jadi app tidak perlu sync penuh.

## Notifikasi push

Server mengirim push lewat FCM HTTP v1 dari job antrean, ke semua token perangkat milik penerima, dan hanya kalau preferensi penerima menyala. Teksnya mengikuti bagian Bahasa dan microcopy di tab utama.

| `type` | Pemicu | Penerima | Preferensi | Channel | Prioritas |
| --- | --- | --- | --- | --- | --- |
| `task_reminder` | `remind_at` tiba dan tugas belum selesai | Penanggung jawab | `task_reminders` | `pengingat` | Tinggi |
| `task_assigned` | Tugas dibuat untuk, atau dialihkan ke, orang lain | Penanggung jawab baru, selain pelaku | `partner_activity` | `aktivitas` | Tinggi |
| `task_completed` | Tugas diselesaikan oleh orang selain pembuatnya | Pembuat tugas | `partner_activity` | `aktivitas` | Normal |
| `savings_deposit` | Setoran baru dicatat | Anggota lain | `savings_activity` | `aktivitas` | Normal |
| `savings_achieved` | Total pertama kali mencapai target | Anggota lain | `savings_activity` | `aktivitas` | Normal |
| `partner_joined` | Pasangan memakai kode undangan | Pengundang | Selalu dikirim | `aktivitas` | Normal |
| `special_date_reminder` | H-7, H-1, dan hari H pukul 08.00 (v0.2) | Kedua anggota; pemilik ruang untuk Ruang Pribadi; hanya pasangan untuk ulang tahun anggota | `special_dates` | `pengingat` | Tinggi |
| `trip_reminder` | H-7 dan H-1 sebelum `start_date` pukul 08.00 (v0.2) | Kedua anggota; pemilik ruang untuk Ruang Pribadi | `trip_reminders` | `pengingat` | Tinggi |
| `savings_reminder` | Hari setor yang dipilih di target, pukul 08.00 (v0.2) | Kedua anggota; pemilik ruang untuk Ruang Pribadi | `savings_reminders` | `pengingat` | Tinggi |
| `memory_created` | Kenangan baru dibuat, dikirim 2 menit kemudian (v0.3) | Anggota lain | `partner_activity` | `aktivitas` | Normal |
| `sync` | Data di Ruang Bersama berubah, paling banyak satu per 10 detik (v1.x) | Anggota lain, selain pelaku | Selalu dikirim | Tanpa notifikasi | Normal |
| `savings_withdrawal` | Penarikan baru dicatat (v1.x) | Anggota lain | `savings_activity` | `aktivitas` | Normal |
| `comment_created` | Komentar baru dibuat (v1.x) | Anggota lain | `partner_activity` | `aktivitas` | Normal |
| `reaction_created` | Reaksi baru disimpan, paling banyak satu per subjek per 10 menit (v1.x) | Anggota lain | `partner_activity` | `aktivitas` | Normal |
| `partner_left` | Pasangan keluar dari Ruang Bersama atau menghapus akunnya (v1.0) | Anggota lain | Selalu dikirim | `aktivitas` | Normal |

Isi pesan yang diterima app, disederhanakan:

```json
{
  "notification": {
    "title": "Beli galon",
    "body": "Tenggat hari ini, 17.00"
  },
  "data": {
    "type": "task_reminder",
    "task_id": "01m4dh1yn0t6cdn39q4f091895",
    "space_id": "01m4dgdt40te98hy85v3fe2t18"
  },
  "android": {
    "priority": "high",
    "notification": {
      "channel_id": "pengingat",
      "tag": "task-01m4dh1yn0t6cdn39q4f091895"
    }
  }
}
```

- Semua nilai di `data` berupa teks, sesuai aturan FCM. Selain `type` dan `space_id`, jenis tugas membawa `task_id` dan jenis tabungan membawa `savings_goal_id`.
- `tag` dipakai di semua jenis (`task-{id}`, `goal-{id}`, `space-{id}`), supaya notifikasi untuk hal yang sama menimpa yang lama. Notifikasi lokal saat app terbuka memakai id angka dari kunci yang sama.
- `special_date_reminder` (v0.2) membawa `special_date_id` untuk tanggal penting, atau `member_id` untuk ulang tahun anggota. `tag`-nya `date-{id}` atau `birthday-{member_id}`, dan saat diketuk app membuka Beranda.
- `trip_reminder` (v0.2) membawa `trip_id`, dengan `tag` `trip-{id}`; saat diketuk app membuka Detail perjalanan.
- `savings_reminder` (v0.2) membawa `savings_goal_id`, dengan `tag` `goal-{id}` dan `visibility` `PRIVATE` seperti push tabungan lain; saat diketuk app membuka Detail target.
- `memory_created` (v0.3) membawa `memory_id`, dengan `tag` `memory-{id}`; saat diketuk app membuka Detail kenangan. Judulnya judul kenangan, isinya "{nama} menyimpan kenangan baru".
- `comment_created` dan `reaction_created` (v1.x) membawa `subject_type` dan `subject_id`, ditambah `savings_goal_id` untuk setoran, dengan `tag` `comment-{subject_id}` atau `reaction-{subject_id}`; saat diketuk app membuka detail subjeknya beserta komentarnya. Rinciannya di bagian Reaksi dan komentar.
- Pengingat tanpa jam berbunyi "Tenggat hari ini". Teks `partner_joined`: "{nama} sudah bergabung. Ruang Bersama kalian siap."
- `partner_left` (v1.0) membawa `space_id`, dengan `tag` `space-{id}`. Judulnya "Ruang Bersama", isinya "{nama} keluar dari Ruang Bersama. Isinya disimpan 30 hari kalau kalian ingin bergabung lagi." Saat diketuk, app menjalankan sync lalu membuka Beranda. Saat ruang dipulihkan, `partner_joined` berbunyi "{nama} sudah bergabung lagi. Isi Ruang Bersama kalian kembali utuh."
- Saat pasangan menghapus akunnya (v1.0), `partner_left` berbunyi "{nama} menghapus akunnya, jadi Ruang Bersama kalian ditutup.", dengan data dan `tag` yang sama.
- Notifikasi tabungan memakai `visibility` `PRIVATE`, jadi nominalnya tidak tampil di layar kunci.
- Saat notifikasi diketuk, app menjalankan sync paling lama 3 detik, lalu membuka detail tugas, detail target, atau Beranda. Kalau datanya belum ada, app membuka layar induknya dengan pesan singkat.
- Saat pertama dibuka, app membuat dua channel: `pengingat` (penting, bersuara) dan `aktivitas` (normal). Keduanya bisa dimatikan terpisah dari pengaturan Android.

## Perubahan dari rancangan awal

Daftar ini menyesuaikan atau menambah rancangan di tab utama, setelah kontrak ditulis dan diperiksa ulang. Centang yang kamu setujui; yang belum, beri komentar di barisnya.

Semua butir di bawah disetujui pada 11 Oktober 2026 dan sudah diterapkan di API. Tiga di antaranya juga menyangkut app, yang belum dibuat: Beranda dari cache lokal, tambah cepat ke daftar aktif pertama, dan tema yang disimpan di HP.

### Perilaku

- [x] `GET /home` dihapus. Beranda dihitung dari cache lokal yang diisi `GET /sync`, jadi tetap cepat dan jalan saat offline. Kriteria FR-15 di tab utama perlu disesuaikan.
- [x] Ruang Bersama baru dibuat saat pasangan memakai kode, bukan saat "Ajak pasangan" diketuk, jadi tidak ada ruang setengah jadi. `POST /spaces` dihapus.
- [x] Satu akun paling banyak punya satu Ruang Bersama di v0.1.
- [x] Tugas yang punya tanggal tapi tanpa jam diingatkan pukul 08.00 di hari tenggatnya.
- [x] Pengingat yang telat lebih dari 30 menit dilewati, dan tugas di daftar arsip tidak diingatkan.
- [x] Setiap ruang selalu punya minimal satu daftar aktif, dan tambah cepat masuk ke daftar aktif pertama.
- [x] Pindah daftar hanya bisa di dalam ruang yang sama.
- [x] Mata uang target hanya dipilih saat target dibuat.
- [x] Preferensi notifikasi dibagi tiga kelompok sesuai layar Pengaturan, bukan per jenis seperti tertulis di FR-11.
- [x] Kalau dua HP mengubah kolom yang sama, yang terakhir sampai di server menang, termasuk perubahan offline yang baru terkirim belakangan.
- [x] `PUT` hanya membuat; data yang sudah ada tidak ditimpa, jadi kiriman ulang dari antrean selalu aman.
- [x] Rupiah disimpan tanpa desimal.
- [x] Tema disimpan di HP, bukan di server.

### Tabel (ERD)

- [x] `users`: tambah `avatar_version`; `birth_date` disimpan sebagai teks supaya tahun boleh kosong.
- [x] `space_invitations.space_id` boleh kosong sampai kode diterima.
- [x] `savings_entries`: tambah `space_id` (disalin dari targetnya), supaya setoran yang terhapus ikut tersinkron.
- [x] `savings_goals`: tambah `achieved_notified_at`.
- [x] Semua `created_at`, `updated_at`, dan `deleted_at` memakai presisi mikrodetik.

### Sudah diputuskan

Dua pertanyaan yang sebelumnya terbuka, diputuskan pada 11 Oktober 2026:

- Teks notifikasi setoran tetap menyebut nominal ("menabung Rp200.000"). Judul tugas pun lewat jalur yang sama, dan nominalnya disembunyikan di layar kunci.
- Lupa kata sandi belum ada di v0.1. Reset dilakukan manual lewat panel admin (sekaligus mencabut semua token akun itu), lalu versi di app dibuat sebelum rilis publik.

### Tanggal penting (v0.2)

Tujuh butir ini menyesuaikan FR-24, UC-20, UC-21, dan tabel `special_dates` di tab utama, dan disetujui pada 22 Oktober 2026.

- [x] Ulang tahun kalian berdua diambil dari `birth_date` di profil, dan setiap anggota di sumber ruang membawa `birth_date`. Tanggal penting manual dipakai untuk anniversary, ulang tahun orang lain, dan tanggal lain, jadi kolom `user_id` tidak dipakai.
- [x] Jenis "Lainnya" (`other`) ikut ditawarkan selain Ulang tahun dan Anniversary.
- [x] Jadwal pengingat tetap H-7, H-1, dan hari H, jadi kolom `remind_days` belum disimpan.
- [x] Tahun boleh kosong, dengan format yang sama seperti `birth_date`.
- [x] Ulang tahun salah satu dari kalian hanya diingatkan ke pasangannya; anniversary ke kalian berdua.
- [x] Jenis push baru `special_date_reminder` di channel `pengingat`, dengan preferensi sendiri `special_dates`.
- [x] Teks push "{judul} 7 hari lagi", "{judul} besok", dan "{judul} hari ini".

### Perjalanan (v0.2)

Tujuh butir ini menyesuaikan FR-17, FR-18, UC-14, UC-15, dan tabel `trips`, `task_lists`, dan `activities` di tab utama, dan disetujui pada 22 Oktober 2026.

- [x] Checklist dibuat app sendiri lewat `PUT /lists/{id}` dengan `trip_id`, tepat setelah perjalanannya, supaya bisa diisi saat offline.
- [x] Checklist tidak dihitung sebagai daftar aktif (`last_list` dan tambah cepat), tidak bisa dihapus atau diarsipkan sendiri (409 `trip_checklist`, kode baru), dan ikut terhapus bersama perjalanannya.
- [x] Tanggal perjalanan boleh kosong untuk rencana yang belum pasti. Tanpa tanggal, statusnya direncanakan dan tidak ada pengingat.
- [x] Target tabungan yang ditautkan harus di ruang yang sama, boleh dipakai beberapa perjalanan, dan tautannya dikosongkan otomatis kalau targetnya dihapus.
- [x] Push baru `trip_reminder` H-7 dan H-1 pukul 08.00 di channel `pengingat`, dengan preferensi sendiri `trip_reminders` dan teks "{judul} 7 hari lagi" atau "{judul} besok", ditambah "Persiapan {selesai} dari {total} beres." kalau checklist berisi.
- [x] Tabel `activities` (feed aktivitas) ditunda, karena belum ada FR atau layar yang memakainya.
- [x] Pengingat setor rutin per target (v0.2, Should) dikerjakan terpisah setelah Perjalanan.

### Pengingat setor rutin (v0.2)

Lima butir ini merancang usulan "Pengingat setor rutin per target" di lingkup fitur tab utama, yang belum punya FR, dan disetujui pada 22 Oktober 2026.

- [x] Jadwal disimpan per target: mingguan (pilih hari) atau bulanan (pilih tanggal 1–31; di bulan yang lebih pendek, jatuh ke hari terakhir bulan itu), pukul 08.00 di zona waktu ruang. Tanpa jadwal, target tidak diingatkan.
- [x] Penerimanya kedua anggota untuk target di Ruang Bersama, dan pemilik untuk target di Ruang Pribadi.
- [x] Target yang tercapai, diarsipkan, atau dihapus tidak diingatkan. Pengingat tetap dikirim walau sudah ada setoran minggu atau bulan itu.
- [x] Push baru `savings_reminder` di channel `pengingat`, dengan preferensi sendiri `savings_reminders`.
- [x] Judul push adalah nama target, isinya "Waktunya menabung. Terkumpul {persen}% dari target.", dan disembunyikan di layar kunci seperti push tabungan lain.

### Kenangan dan foto (v0.3)

Sepuluh butir ini menyesuaikan FR-19, FR-20, FR-21, UC-16, UC-17, tabel `memories` dan `memory_photos`, serta baris API dan notifikasi Kenangan di tab utama, dan disetujui pada 23 Oktober 2026. Butir pertama menjawab pertanyaan terbuka soal pin lokasi.

- [x] Pin lokasi di peta belum ada di v0.3. Kenangan cukup menyimpan nama tempat, jadi `latitude` dan `longitude` belum dibuat; pin dan Peta kenangan menyusul bersama di v1.x.
- [x] Foto diunggah satu per satu ke `POST /memory-photos/{id}` dengan ID dari HP, bukan `POST /memories/{id}/photos`, supaya kiriman ulang dari antrean aman dan foto bisa ditambahkan saat offline. "Beberapa sekaligus" (FR-20) dikerjakan app dengan mengirimnya berurutan.
- [x] App mengecilkan foto ke sisi terpanjang 2560 px sebelum unggah. Server menyimpan ulang sebagai JPEG tanpa metadata dan membuat thumbnail 800 px. Batasnya 10 MB per file dan 50 foto per kenangan.
- [x] URL foto berlaku 7 hari seperti foto profil. URL baru diambil lewat endpoint baca baru `GET /memories/{id}/photos`.
- [x] Urutan foto memakai `position` seperti daftar, dan foto sampul adalah yang terkecil. "Jadikan sampul" cukup mengubah `position`.
- [x] Satu perjalanan boleh punya beberapa kenangan. Menghapus perjalanan hanya melepas tautan kenangannya, tidak ikut menghapusnya.
- [x] Menghapus foto atau kenangan langsung menghapus filenya dari R2, jadi tidak bisa dipulihkan dari server. Di app, hapus foto memakai Urungkan 5 detik, dan hapus kenangan berfoto memakai konfirmasi.
- [x] Push baru `memory_created` ke anggota lain di channel `aktivitas`, dengan preferensi `partner_activity` yang sudah ada. Dikirim 2 menit setelah kenangan dibuat supaya fotonya sempat terunggah, dengan judul kenangan sebagai judul dan isi "{nama} menyimpan kenangan baru". Menambah foto ke kenangan lama tidak mengirim push.
- [x] Tabel `memory_photos` tanpa `disk` dan `mime`, karena semua foto berupa JPEG di R2, dan ditambah `space_id` (disalin dari kenangannya) supaya foto yang terhapus ikut tersinkron.
- [x] Foto disimpan di bucket R2 yang sudah dipakai foto profil, di folder `memories/`, jadi tidak perlu bucket atau kunci baru.

### Tugas berulang (v1.0)

Sepuluh butir ini merancang "Tugas berulang" di lingkup fitur tab utama, yang belum punya FR, dari usulan sesi Flutter, dan disetujui pada 24 Oktober 2026.

- [x] Tugas berikutnya dibuat server saat tugas berulang diselesaikan, bukan oleh HP, supaya centang dari dua HP tidak membuat duplikat. Saat offline, tugas berikutnya baru muncul setelah centangnya terkirim.
- [x] Pilihan ulang: harian, mingguan, bulanan, dan tahunan, masing-masing dengan "setiap N" (1–99), misalnya setiap 2 minggu atau setiap 3 bulan.
- [x] Mingguan di beberapa hari sekaligus belum ada di v1.0. Untuk "Selasa dan Jumat", buat dua tugas mingguan; pilihan beberapa hari bisa ditambah nanti tanpa mengubah tugas yang sudah ada.
- [x] Tugas berulang wajib punya `due_date` sebagai patokan. Bulan yang lebih pendek memakai hari terakhirnya lalu kembali ke tanggal aslinya, dan 29 Februari jatuh ke 28 Februari di tahun biasa.
- [x] Tenggat berikutnya adalah kejadian pertama sesudah tenggat lama atau tanggal selesai, mana yang lebih akhir, jadi kejadian yang terlewat tidak menumpuk.
- [x] Tugas berikutnya membawa judul, catatan, daftar, prioritas, penanggung jawab, jam, posisi, dan aturan ulang. `created_by` tetap pembuat aslinya, dan tidak ada push `task_assigned`.
- [x] Urungkan yang sudah terkirim ikut menghapus tugas berikutnya kalau belum disentuh siapa pun; kalau sudah diubah, tugas itu dibiarkan. Satu tugas paling banyak punya satu tugas berikutnya, jadi centang ulang tidak membuat duplikat.
- [x] Pengulangan berhenti dengan mengosongkan aturan ulang atau menghapus tugas yang belum selesai. Mengosongkan `due_date` ikut mengosongkan aturan ulang.
- [x] Checklist perjalanan tidak bisa berulang (422).
- [x] Balasan PUT dan PATCH tugas membawa `next_task`, dan sumber tugas punya kolom baru `repeat_frequency`, `repeat_interval`, dan `next_task_id`, jadi app versi baru sync penuh sekali.

### Push sinkron (v1.x)

Lima butir ini merancang sinkron langsung (realtime) di lingkup v1.x dari usulan sesi Flutter, dan disetujui pada 25 Oktober 2026.

- [x] Push `sync` berisi data saja (`type` dan `space_id`), tanpa judul dan isi, dengan prioritas normal dan `collapse_key` `sync-{space_id}`.
- [x] Dikirim untuk setiap perubahan di Ruang Bersama yang terlihat di `GET /sync`, ke anggota lain di semua perangkatnya. Pelaku tidak menerimanya, termasuk di HP lain miliknya.
- [x] Selalu dikirim, tanpa preferensi notifikasi.
- [x] Paling banyak satu push per penerima setiap 10 detik: perubahan pertama dikirim langsung, dan sisanya dalam jeda itu digabung di akhir jeda. Ini berbeda dari usulan yang menunda semua push 10 detik, karena dengan cara itu pasangan selalu baru melihat perubahan 10–13 detik kemudian, tidak jauh beda dari sync tiap 30 detik yang rata-rata menunggu 15 detik.
- [x] Push ini hanya mempercepat: app tetap sync saat dibuka, kembali aktif, dan tiap 30 detik selama tampil, dan push yang datang tanpa perubahan baru cukup dijawab dengan sync biasa.

### Penarikan tabungan (v1.x)

Tujuh butir ini merancang penarikan tabungan di lingkup v1.x dari usulan sesi Flutter, dan disetujui pada 29 Oktober 2026.

- [x] Penarikan dicatat sebagai setoran dengan `type` `withdrawal`, dan `amount` tetap lebih dari 0. Tidak ada endpoint atau koleksi baru.
- [x] `type` hanya diisi saat `PUT` dan tidak bisa diubah lewat `PATCH`.
- [x] Total = setoran dikurangi penarikan, dipakai untuk `achieved_at`, persen `savings_reminder`, dan progres di app. Total yang turun di bawah target membuat `achieved_at` kembali `null`, tetapi `savings_achieved` tetap sekali per target.
- [x] Penarikan yang melebihi saldo tidak ditolak; app yang mencegahnya, dan total boleh negatif. Persen di `savings_reminder` paling kecil 0.
- [x] Push baru `savings_withdrawal` ke anggota lain saat penarikan dibuat: preferensi `savings_activity`, channel `aktivitas`, `visibility` `PRIVATE`, dan `tag` `goal-{id}`.
- [x] Teks push: judulnya nama target, isinya "{nama} mengambil {nominal}". Ini berbeda dari usulan "{nama} mengambil {nominal} dari {target}", karena nama target sudah menjadi judul, sama seperti "{nama} menabung {nominal}" di push setoran.
- [x] Kedua HP memakai app baru sebelum penarikan pertama dicatat, karena app lama menghitung penarikan sebagai setoran.

### Pin lokasi kenangan (v1.x)

Delapan butir ini merancang pin lokasi dan pencarian tempat di lingkup v1.x dari usulan sesi Flutter, dan disetujui pada 29 Oktober 2026. Butir terakhir adalah keputusan pemilik untuk memakai server Nominatim publik.

- [x] Kenangan punya `latitude` dan `longitude` yang selalu berpasangan; mengirim hanya satu, termasuk lewat `PATCH`, ditolak 422.
- [x] Server membulatkan koordinat ke 6 desimal, bukan menolak angka yang lebih panjang. Ini berbeda dari usulan, supaya koordinat GPS dari HP tidak perlu dibulatkan app dulu.
- [x] `GET /places?q=` lewat server ke Nominatim: paling banyak 8 hasil berisi `name`, `address`, `latitude`, dan `longitude`, dengan cache 1 hari dan paling banyak 1 request per detik ke Nominatim untuk seluruh app.
- [x] App mencari hanya saat tombol cari ditekan, tidak sambil mengetik, karena autocomplete dilarang kebijakan Nominatim.
- [x] Batas 20 pencarian per menit per akun, dan 429 `too_many_requests` kalau giliran ke Nominatim tidak didapat dalam 2 detik.
- [x] Kode error baru 503 `places_unavailable` saat Nominatim gagal atau pencarian dimatikan; pin manual di peta tetap bisa.
- [x] Saklar `PLACES_SEARCH_ENABLED` di server untuk mematikan pencarian tanpa update app.
- [x] Pemilik memutuskan memakai server Nominatim publik dan bertanggung jawab mematuhi [kebijakannya](https://operations.osmfoundation.org/policies/nominatim/): paling banyak 1 request per detik untuk seluruh app, tanpa autocomplete, dengan atribusi OpenStreetMap, dan siap berhenti kapan pun OSMF meminta. Kalau pemakaian membesar saat rilis publik, beralih ke Nominatim sendiri atau penyedia berbayar.

### Reaksi dan komentar (v1.x)

Sembilan butir ini merancang reaksi dan komentar di lingkup v1.x dari usulan sesi Flutter, dan disetujui pada 29 Oktober 2026.

- [x] Komentar dan reaksi untuk tugas, setoran (termasuk penarikan), dan kenangan, hanya di Ruang Bersama. Subjek di Ruang Pribadi ditolak 422.
- [x] Tabel dan koleksi sync baru `comments` dan `reactions`, dengan `subject_type`, `subject_id`, dan `space_id` yang disalin dari subjeknya. App versi baru sync penuh sekali.
- [x] Hanya penulis yang boleh mengubah dan menghapus komentarnya, dan hanya pemiliknya yang boleh menghapus reaksi (403 `forbidden`). Ini berbeda dari data lain, yang boleh diubah kedua anggota.
- [x] Satu reaksi per orang per subjek. Reaksi baru menggantikan yang lama, dan reaksi tidak punya `PATCH`.
- [x] Menghapus subjek, langsung atau berantai, ikut menghapus komentar dan reaksinya. Subjek di daftar atau target yang diarsipkan tetap bisa dikomentari.
- [x] Tugas berikutnya yang sudah diberi komentar atau reaksi tidak ikut terhapus saat centang tugas sebelumnya diurungkan, supaya komentar pasangan tidak hilang. Ini menambah aturan Urungkan di Tugas berulang (v1.0).
- [x] Push baru `comment_created` ke anggota lain: preferensi `partner_activity`, channel `aktivitas`, `tag` `comment-{subject_id}`, judul subjek sebagai judul, dan isi "{nama}: {80 karakter pertama}".
- [x] Push baru `reaction_created` ke anggota lain, paling banyak satu per subjek per 10 menit, dengan isi "{nama} memberi reaksi {emoji}". Ini berbeda dari usulan "ke pemilik subjek", karena reaksi sering ditujukan ke orang yang menyelesaikan tugas, bukan pembuatnya. Pemilik memilih push ini daripada reaksi tanpa push, karena tanpa feed aktivitas reaksi hanya terlihat kalau subjeknya dibuka.
- [x] Push komentar dan reaksi pada setoran membawa `savings_goal_id` dan memakai `visibility` `PRIVATE`, seperti push tabungan lain.

### Masuk dengan Google (v1.x)

Sembilan butir ini merancang masuk dengan Google di lingkup v1.x dari usulan sesi Flutter, dan disetujui pada 30 Oktober 2026.

- [x] `POST /auth/google` menukar ID token Google dengan token Denly Days. Bentuk balasannya sama dengan daftar dan masuk, dengan 201 untuk akun baru dan 200 untuk akun yang sudah ada. Ini berbeda dari usulan "balasan sama dengan `/auth/login`", supaya app tahu akun baru saja dibuat.
- [x] Server memeriksa token sendiri dengan kunci publik Google yang disimpan di cache, bukan lewat endpoint tokeninfo Google, yang menurut Google tidak untuk produksi. Pemeriksaannya memakai `firebase/php-jwt`, yang sudah terpasang lewat `kreait/laravel-firebase` dan kini dicatat sebagai dependensi langsung, jadi tidak ada paket baru.
- [x] Email Google yang sama dengan akun yang sudah ada langsung disambungkan ke akun itu, asalkan Google menandainya terverifikasi, dan kata sandinya tetap berlaku. Dengan begitu akun kalian berdua bisa langsung masuk dengan Google. Pemilik memilih ini daripada menolak email yang sama, yang membuat akun harus disambungkan dari Pengaturan setelah masuk dengan kata sandi dan butuh endpoint serta layar tambahan.
- [x] Akun dikenali dari ID akun Google lebih dulu, jadi email akun Google yang berubah tidak memutus akses. Email akun Denly Days tidak ikut berubah.
- [x] Akun baru dibuat seperti daftar biasa, dengan nama dari Google dan tanpa kata sandi. Masuk dengan kata sandi untuk akun itu dibalas `invalid_credentials`, dan admin bisa membuatkan kata sandi lewat "Reset kata sandi".
- [x] Kode error baru 422 `google_token_invalid` untuk token yang salah, kedaluwarsa, untuk app lain, atau yang emailnya belum terverifikasi. Kunci Google yang tidak bisa diambil dibalas 503 `server_error`.
- [x] Batasnya 5 per menit per IP, seperti daftar, karena email baru diketahui setelah token diperiksa.
- [x] Sumber user tidak berubah, dan foto profil Google tidak diambil, jadi app tetap memakai inisial atau foto yang diunggah sendiri.
- [x] `GOOGLE_CLIENT_ID` di server berisi client ID klien Web yang dibuat pemilik pada 29 Oktober, dan app memakai nilai yang sama untuk `serverClientId`. Selama layar persetujuan berstatus Testing, hanya pengguna uji yang bisa masuk dengan Google; statusnya diubah sebelum rilis publik.

### Keluar dari Ruang Bersama (v1.0)

Sepuluh butir ini merancang keluar dari Ruang Bersama, salah satu syarat sebelum rilis publik di tab utama, dan disetujui pada 30 Oktober 2026. Butir pertama adalah pilihan pemilik soal nasib data bersama.

- [x] Ruang Bersama ditutup untuk kalian berdua saat salah satu keluar, sesuai pilihan pemilik, bukan tetap untuk yang tinggal atau jadi arsip baca-saja. Salah satu dari kalian bisa menutupnya tanpa persetujuan pasangan.
- [x] Keluar memakai `DELETE /spaces/{id}`: 204, aman diulang, dan tidak lewat antrean. Ruang Pribadi tidak bisa dihapus (422 `validation_failed`), dan tidak ada kode error baru.
- [x] Isinya disimpan 30 hari. Kalau kalian berdua bergabung lagi lewat kode undangan dalam waktu itu, ruang lama otomatis dipulihkan utuh, bukan dibuat ruang baru.
- [x] Lewat 30 hari, ruang beserta seluruh isi dan fotonya dihapus permanen dari database dan R2 oleh perintah terjadwal harian, jadi tidak bisa dipulihkan lagi, juga oleh admin.
- [x] Ruang yang ditutup tidak dihitung sebagai Ruang Bersama, jadi kalian berdua bisa langsung berpasangan lagi dengan siapa pun.
- [x] Perubahan yang belum terkirim dari HP mana pun saat ruang ditutup ikut hilang. App mengirim antreannya dulu sebelum keluar, tetapi antrean HP pasangan yang sedang offline tidak bisa diselamatkan.
- [x] Push baru `partner_left` ke pasangan: selalu dikirim, channel `aktivitas`, dengan isi "{nama} keluar dari Ruang Bersama. Isinya disimpan 30 hari kalau kalian ingin bergabung lagi." Saat ruang dipulihkan, `partner_joined` berbunyi "{nama} sudah bergabung lagi. Isi Ruang Bersama kalian kembali utuh."
- [x] Pengingat dan push lain untuk ruang yang ditutup berhenti, dan yang terlewat selama ditutup tidak dikirim menyusul setelah dipulihkan.
- [x] Ruang yang ditutup tiba lewat sync dengan `deleted_at` terisi, tanpa isinya, dan app menghapus semuanya dari cache. Saat dipulihkan, server memperbarui `updated_at` semua isinya supaya terkirim lagi lewat `since`. Tidak ada koleksi atau kolom baru, jadi app tidak perlu sync penuh.
- [x] Hapus akun (v1.0) belum termasuk. Nasib Ruang Bersama saat akun dihapus diputuskan di kontraknya sendiri, karena pasangan tidak bisa bergabung kembali dengan akun yang sudah dihapus.

### Hapus akun (v1.0)

Sepuluh butir ini merancang hapus akun, bagian "Ekspor data + hapus akun" di lingkup fitur dan syarat sebelum rilis publik di tab utama, dan disetujui pada 30 Oktober 2026. Ekspor data menyusul sebagai kontrak sendiri.

- [x] Hapus akun lewat `DELETE /me` dengan `password`, atau `id_token` dari akun Google yang tersambung, supaya orang yang memegang HP yang tidak terkunci tidak bisa menghapus akun. Salah konfirmasi dibalas 422 `invalid_credentials` ("Kata sandi salah.") atau `google_token_invalid`, jadi tidak ada kode error baru. Batasnya 5 per menit per akun.
- [x] Semua data dihapus permanen saat itu juga, tanpa masa tenggang dan tanpa pembatalan: profil, foto profil, Ruang Pribadi beserta isi dan fotonya, perangkat, token di semua HP, dan kode undangan.
- [x] Ruang Bersama ikut ditutup untuk kalian berdua, dan isinya beserta foto langsung dihapus permanen tanpa masa 30 hari, karena tidak bisa dipulihkan tanpa akun itu. Ini akibat pilihan "ditutup untuk berdua", jadi konfirmasi di app menyebutnya. Ruang Bersama lama yang sudah ditutup juga ikut dikosongkan.
- [x] Ruangnya sendiri tetap ada tanpa isi sampai `spaces:purge`, supaya HP pasangan menghapusnya lewat sync seperti saat keluar.
- [x] Pasangan menerima `partner_left` dengan teks "{nama} menghapus akunnya, jadi Ruang Bersama kalian ditutup.", tanpa jenis push baru.
- [x] Halaman publik `GET /hapus-akun` untuk Google Play: cara menghapus dari app, atau lewat email dari alamat akun ke alamat di variabel baru `SUPPORT_EMAIL`, yang diisi pemilik di Coolify. Permintaan lewat email diproses paling lambat 3 × 24 jam, sejalan dengan batas menghentikan pemrosesan di Pasal 40 UU PDP.
- [x] Aksi baru "Hapus akun" di panel admin untuk permintaan lewat email, dengan proses yang sama. Akun admin sendiri tidak bisa dihapus dari panel.
- [x] Cadangan database tidak diubah: data yang dihapus ikut hilang saat cadangan lama terhapus otomatis, paling lama 30 hari, dan halaman hapus akun menyebutnya.
- [x] Email dan akun Google yang sama bisa dipakai lagi untuk daftar sebagai akun baru, tanpa data lama.
- [x] Ekspor data dikerjakan sesudah ini sebagai kontrak sendiri.

### Ekspor data (v1.0)

Sepuluh butir ini merancang ekspor data, bagian "Ekspor data + hapus akun" di lingkup fitur dan syarat sebelum rilis publik di tab utama, dan disetujui pada 31 Oktober 2026. Butir pertama adalah pilihan pemilik soal bentuk file.

- [x] Ekspor lewat `GET /me/export`, yang langsung membalas satu file JSON, tanpa antrean atau menunggu. Pemilik memilih JSON dengan tautan foto, bukan ZIP berisi foto yang disiapkan di belakang layar; ZIP bisa ditambah nanti.
- [x] Isinya sama dengan yang terlihat di app: profil, Ruang Pribadi, Ruang Bersama yang masih terbuka beserta profil anggotanya, dan semua isinya yang belum dihapus, termasuk data buatan pasangan di Ruang Bersama.
- [x] Isi Ruang Pribadi pasangan, data yang sudah dihapus, dan Ruang Bersama yang sudah ditutup tidak ikut.
- [x] Setiap koleksi memakai bentuk sumber yang sama dengan sync, jadi tidak ada bentuk data baru. Filenya tanpa pembungkus `data`, dengan `app`, `format_version` 1, dan `exported_at`.
- [x] Foto ikut lewat tautan baru yang berlaku 7 hari, batas terlama R2, bukan sebagai file.
- [x] Kata sandi, token masuk, token FCM, dan ID akun Google tidak ikut; `devices` hanya menyebut platform dan waktunya.
- [x] Batasnya 5 per jam per akun, karena satu ekspor membaca seluruh data akun.
- [x] Nama filenya `denly-days-{tanggal}.json`, dengan tanggal di zona waktu akun.
- [x] Halaman `/hapus-akun` dan konfirmasi keluar serta hapus akun di app menyarankan ekspor dulu.
- [x] Panel admin tidak punya ekspor, karena admin tidak boleh melihat isi data pengguna. Impor dari file ekspor belum ada.
