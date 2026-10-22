# Kontrak API v0.1

> Salinan dari dokumen perencanaan di claude.ai (tab "Kontrak API v0.1"), per 22 Oktober 2026. Kalau berbeda, dokumen itu yang berlaku. Setiap kali kontrak berubah, perbarui salinan ini di repo `api` dan `mobile`.

Rilis v0.1 butuh 25 endpoint di bawah `/api/v1`: semua data dibaca lewat satu pintu, `GET /sync`, dan setiap perubahan data dari antrean offline aman dikirim ulang. Laravel membangunnya persis seperti di sini, dan Flutter memanggilnya dengan bentuk data yang sama. Setelah endpoint jadi, dokumentasi otomatis Scramble menjadi acuan harian. Rilis v0.2 menambah 3 endpoint untuk Tanggal penting.

## Ringkasan endpoint

Semua endpoint butuh token Sanctum, kecuali daftar dan masuk. Hanya tiga yang membaca data (`GET /me`, `GET /spaces`, `GET /sync`); sisanya menulis.

| Modul | Method | Endpoint | Fungsi |
| --- | --- | --- | --- |
| Akun | POST | `/auth/register` | Daftar, membuat Ruang Pribadi, menerima token |
| Akun | POST | `/auth/login` | Masuk, menerima token |
| Akun | POST | `/auth/logout` | Mencabut token HP ini dan menghapus token FCM-nya |
| Akun | GET | `/me` | Profil sendiri, preferensi notifikasi, dan kode undangan aktif |
| Akun | PATCH | `/me` | Ubah nama, warna diri, zona waktu, mata uang, preferensi, tanggal lahir |
| Akun | POST | `/me/avatar` | Unggah foto profil |
| Akun | DELETE | `/me/avatar` | Hapus foto profil |
| Perangkat | POST | `/devices` | Daftarkan token FCM HP ini |
| Perangkat | DELETE | `/devices/{token}` | Hapus token FCM |
| Ruang | GET | `/spaces` | Ruang tempatmu menjadi anggota, beserta profil anggotanya |
| Ruang | POST | `/invitations` | Ambil kode undangan aktif, atau buat yang baru |
| Ruang | POST | `/invitations/accept` | Pakai kode; Ruang Bersama dibuat untuk kalian berdua |
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

## Konvensi

Semua request dan balasan memakai JSON dengan nama kolom `snake_case`, sama dengan kamus data di tab utama. Aturan di bagian ini berlaku untuk semua endpoint.

### Header

| Header | Isi | Kapan |
| --- | --- | --- |
| `Accept` | `application/json` | Selalu |
| `Authorization` | `Bearer <token>` | Semua endpoint kecuali daftar dan masuk |
| `Content-Type` | `application/json` | Request yang punya body; `multipart/form-data` untuk foto profil |

Pesan error selalu berbahasa Indonesia (`APP_LOCALE=id`, terjemahan dari paket laravel-lang). Semua route `api/*` dibalas JSON walau header `Accept` terlewat, lewat `shouldRenderJsonWhen()` di `bootstrap/app.php`.

### Bentuk balasan

- Satu data: `{"data": {...}}`. Sinkron: `{"data": {...}, "meta": {...}}`.
- Kolom kosong tetap dikirim sebagai `null`, supaya model freezed di Flutter selalu lengkap.
- `DELETE` dan `POST /auth/logout` membalas 204 tanpa body.
- Angka desimal seperti `position` bisa tiba sebagai `1024` atau `1024.5`; di Dart baca sebagai `num`.

### ID

- Semua ID berupa ULID 26 karakter huruf kecil, contoh `01m4dg5jegv6sp4xav8efqzcqe`.
- ID daftar, tugas, target, dan setoran dibuat di HP; ID akun, ruang, dan undangan dibuat server.
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
- **Induk terhapus atau diarsipkan:** tugas ke daftar yang sudah dihapus dibalas 409 `parent_deleted`, dan ke daftar yang diarsipkan 409 `parent_archived`, baik saat dibuat maupun dipindah. Setoran ke target yang sudah dihapus juga dibalas 409 `parent_deleted`.
- **Akses:** data di ruang yang bukan ruangmu dianggap tidak ada (404, bukan 403).
- **Lewat antrean:** hanya PUT, PATCH, dan DELETE untuk daftar, tugas, target, dan setoran, ditambah `PATCH /me`. Endpoint lain dipanggil langsung dan butuh koneksi.

### Batas request

| Endpoint | Batas |
| --- | --- |
| `/auth/login` | 5 per menit per email + IP |
| `/auth/register` | 5 per menit per IP |
| `/invitations/accept` | 10 per menit per akun dan per IP |
| Lainnya | 120 per menit per akun, cukup untuk antrean yang menumpuk setelah offline |

Di luar `/api/v1` ada `GET /up` (bawaan Laravel) untuk health check Coolify, dan `/docs/api` untuk dokumentasi Scramble, yang secara bawaan hanya terbuka di environment lokal.

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
| 403 | `forbidden` | Tidak boleh melakukan ini | Buang dari antrean, pakai `current`, tampilkan pesan |
| 404 | `not_found` | Data tidak ada, atau bukan di ruangmu | Buang dari antrean, hapus data itu dari cache |
| 404 | `route_not_found` | Endpoint tidak dikenal, biasanya versi app dan server berbeda | Simpan antrean, coba lagi nanti, tawarkan pembaruan app |
| 409 | `parent_deleted`, `parent_archived` | Daftar atau target induknya sudah dihapus, atau daftarnya diarsipkan | Tugas: pindahkan ke daftar aktif pertama, lalu kirim ulang. Setoran: buang dan beri tahu |
| 409 | `last_list`, `id_conflict` | Bentrok dengan aturan; rinciannya di bagian masing-masing | Buang dari antrean, pakai `current`, tampilkan pesan |
| 409 | `shared_space_exists` | Sudah punya Ruang Bersama dengan orang lain | Tampilkan pesan; endpoint undangan tidak lewat antrean |
| 410 | `resource_deleted` | Sudah dihapus, biasanya oleh pasangan | Buang dari antrean, hapus data itu dari cache |
| 422 | `validation_failed`, `invalid_credentials`, `invitation_invalid` | Isi request tidak valid | Tampilkan pesan; kalau dari antrean, buang dan pakai `current` |
| 429 | `too_many_requests` | Terlalu sering | Tunggu sesuai header `Retry-After`, lalu coba lagi |
| 5xx, tanpa koneksi, atau balasan bukan JSON | `server_error` atau tidak ada | Server, proxy, atau jaringan bermasalah | Coba lagi dengan jeda 2, 4, 8 detik dan seterusnya, maksimal 5 menit |

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
    "special_dates": true
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
- `DELETE /devices/{token}` → 204, hanya untuk token milik akunmu sendiri. Server juga menghapus token yang ditolak FCM sebagai `UNREGISTERED`.

## Ruang dan undangan

Ruang Bersama baru dibuat saat pasangan memakai kode undangan, jadi ruang itu selalu berisi tepat 2 orang. Setiap akun punya tepat satu Ruang Pribadi dan, di v0.1, paling banyak satu Ruang Bersama.

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
- `space_invitations.space_id` baru terisi saat kode diterima. Keluar dari Ruang Bersama belum ada di v0.1, tapi wajib ada sebelum rilis publik.

## Daftar dan tugas

Setiap ruang selalu punya minimal satu daftar aktif (tidak diarsipkan). Tambah cepat (FR-06) memasukkan tugas ke daftar aktif dengan `position` terkecil di ruang yang dipilih.

### Sumber daftar

```json
{
  "id": "01m4dh0420zqexwxs1n4yw5jn2",
  "space_id": "01m4dgdt40te98hy85v3fe2t18",
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

- `DELETE /lists/{id}` ikut menghapus semua tugas di dalamnya (soft delete) dan memperbarui `updated_at` tugas-tugas itu.
- Menghapus atau mengarsipkan daftar aktif terakhir di sebuah ruang ditolak 409 `last_list`. Server mengunci baris ruang saat menghitung, supaya dua penghapusan bersamaan tidak sama-sama lolos.
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
  "priority": 0,
  "position": 2048,
  "assignee_ids": ["01m4dga4y03c5ratqsc31zc916"],
  "remind_at": "2026-10-09T10:00:00.000000Z",
  "completed_at": null,
  "completed_by": null,
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
| `due_date` | opsional | `YYYY-MM-DD`. Mengosongkannya ikut mengosongkan `due_time` |
| `due_time` | opsional | `HH:MM` dalam zona waktu ruang; butuh `due_date` |
| `priority` | opsional | 0 tanpa, 1 rendah, 2 sedang, 3 tinggi; bawaan 0 |
| `position` | wajib | Angka desimal, dihitung app |
| `assignee_ids` | opsional | Larik berisi 1–2 anggota ruang; tidak diisi saat PUT berarti pembuat. Dua ID berarti "Kita". `null` atau `[]` ditolak 422 |
| `completed_at` | opsional | Waktu dicentang di HP, dengan `Z` atau offset; `null` berarti batal selesai. Server mengisi `completed_by` |

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

## Tabungan

Progres dihitung di HP dari daftar setoran. Server menghitung total hanya untuk menandai target tercapai dan mengirim notifikasi.

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

- `user_id` diisi server dengan akun yang mencatat. `type` di v0.1 selalu `deposit`; penarikan menyusul di v1.x.
- Kedua anggota boleh mengubah dan menghapus setoran siapa pun.
- Setiap kali setoran dibuat, diubah, atau dihapus, atau `target_amount` berubah, server menghitung ulang total dalam transaksi yang mengunci baris target. Total mencapai target: `achieved_at` diisi. Total turun lagi di bawah target: `achieved_at` kembali `null`.
- Push `savings_achieved` dikirim paling banyak sekali per target, dicatat di `achieved_notified_at`. Kalau push itu terkirim karena sebuah setoran, push `savings_deposit` untuk setoran yang sama tidak dikirim.
- `DELETE /savings-goals/{id}` ikut menghapus semua setorannya dan memperbarui `updated_at` setoran itu. Setoran ke target yang sudah dihapus dibalas 409 `parent_deleted`; setoran ke target yang diarsipkan tetap diterima.

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
    "special_dates": []
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
- `cursor` yang bukan buatan server dibalas 422 `validation_failed`. App lalu mengulang sync dari `since` yang tersimpan.
- Kalau `cursor` dikirim, `since` tidak dibaca.
- Saat koleksi baru ditambahkan (v0.2: `special_dates`), `cursor` yang dibuat sebelum deploy ditolak sekali dengan 422, lalu app mengulang dari `since`.

### Aturan di HP

- Hanya satu sync berjalan pada satu waktu. Sebelum menarik data, app mengirim operasi antrean yang bisa dikirim saat itu, lalu tetap menarik walau masih ada yang menunggu jeda Urungkan atau coba ulang.
- Kalau database lokal akun itu sudah punya kursor, misalnya saat masuk lagi setelah keluar, app melanjutkan dengan `since`. Dengan begitu data yang dihapus pasangan selama itu ikut terhapus di HP.
- Baris dari server ditimpa berdasarkan `id`, tanpa pengecekan foreign key selama sync, karena tugas bisa tiba sebelum daftarnya.
- Data yang masih punya operasi di antrean dilindungi: kolomnya tidak ditimpa, dan baris yang DELETE-nya masih di antrean tidak muncul lagi.
- Baris yang `updated_at`-nya lebih tua dari salinan lokal diabaikan.
- Saat induk tiba dengan `deleted_at` terisi, app ikut menghapus anak-anaknya di cache, kecuali anak yang masih punya operasi di antrean; nasibnya ditentukan balasan server (`parent_deleted`).
- Sync penuh hanya menambah dan memperbarui.
- Sync berjalan saat app dibuka atau kembali aktif, saat layar ditarik untuk menyegarkan, dan saat push masuk ketika app terbuka.
- App versi baru yang mengenal koleksi baru (v0.2: `special_dates`) menjalankan sync penuh sekali sebelum kembali memakai `since`. Tanpa itu, data yang dibuat pasangan selama app masih versi lama tidak pernah sampai, karena `since` sudah melewatinya.

### Antrean di HP

- Isi antrean dikirim berurutan, satu per satu. Setiap operasi ditandai "pernah dikirim" sebelum dikirim.
- Perubahan baru hanya boleh digabung ke operasi untuk data yang sama kalau operasi itu yang paling akhir di antrean, belum pernah dikirim, dan tidak sedang menunggu Urungkan. Selain itu, perubahan masuk sebagai operasi baru di ujung antrean. Nilai "sebelum diubah" yang disimpan adalah yang paling awal.
- Kalau `PUT` dibalas 200 (sudah tersimpan dari kiriman sebelumnya), balasannya diterapkan seperti baris sync, dengan kolom di antrean tetap dilindungi. Tidak ada operasi tambahan.
- Selesai dan hapus tugas masuk antrean sebagai operasi tersendiri dengan jeda kirim 5 detik selama tombol Urungkan tampil (FR-08). Urungkan hanya menghapus operasi itu, jadi pasangan tidak menerima notifikasi yang dibatalkan.
- Di layar detail, perubahan dikirim 1 detik setelah berhenti mengetik, dan langsung ditulis ke antrean saat app masuk ke latar belakang.

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
- Pengingat tanpa jam berbunyi "Tenggat hari ini". Teks `partner_joined`: "{nama} sudah bergabung. Ruang Bersama kalian siap."
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
