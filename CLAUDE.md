# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

API untuk aplikasi to-do berdua (tugas, tabungan, liburan, kenangan). Klien satu-satunya adalah app Flutter di repo mobile. Pengguna awal: dua orang, pemilik repo dan pasangannya; rilis publik menyusul.

**Sumber kebenaran: `docs/kontrak-api.md`.** Baca file itu sebelum membuat atau mengubah endpoint, validasi, resource, error, sync, undangan, atau notifikasi. Kalau kode dan kontrak berbeda, ikuti kontrak lalu tanyakan. Kontrak itu salinan dari dokumen perencanaan di claude.ai; perubahan kontrak dilakukan di dokumen dulu, baru salinannya diperbarui.

Blok Laravel Boost di bagian bawah file ini berisi panduan umum Laravel dan ditulis ulang oleh `php artisan boost:update` (jalan otomatis setelah `composer update`), jadi jangan diedit; aturan proyek ditulis di luar blok itu, dan tag pembuka blok jangan pernah ditulis ulang di bagian proyek. Kalau keduanya bertentangan, aturan proyek yang berlaku. Contoh: deploy proyek ini lewat Coolify, bukan Laravel Cloud.

## Kondisi repo (per 11 Oktober 2026)

Seluruh cakupan v0.1 sudah dibangun, bertes, dan ter-deploy di `https://todolist.iqbalfhz.my.id`: ke-25 endpoint di kontrak, pengingat terjadwal, keenam jenis push, panel admin, dan seeder lokal. Daftar pekerjaan beserta statusnya ada di `TASKS.md`; perbarui file itu setiap kali memulai atau menyelesaikan pekerjaan.

Belum beres:

- Uji coba ke layanan asli: disk `r2` dan FCM belum pernah dipakai dengan kredensial sungguhan, dan kredensialnya belum diisi di server. Sampai itu ada, unggah foto profil gagal dan push tidak terkirim.
- Di server baru `/up`, panel admin, dan balasan error API yang sudah dicoba. Alur daftar sampai sync belum pernah dijalankan di sana; yang pertama mencobanya adalah app Flutter.
- Docker tidak terpasang di mesin pengembang, jadi perubahan pada `Dockerfile` atau folder `docker/` baru teruji saat di-deploy.
- Teks push selain pengingat dan `partner_joined` masih sementara di `lang/id/push.php`, karena bagian "Bahasa dan microcopy" belum disalin ke repo.
- `personal_access_tokens.id` tetap bigint bawaan Sanctum, mengikuti contoh token `"1|abc..."` di kontrak; hanya `tokenable_id` yang ULID.

Perbarui atau hapus butir di atas begitu selesai dikerjakan.

## Cakupan v0.1 (yang sedang dibangun)

- Akun dan profil (termasuk foto profil), perangkat (token FCM)
- Ruang Pribadi, Ruang Bersama, kode undangan
- Daftar tugas, tugas, penanggung jawab, pengingat
- Tabungan: target dan setoran
- Sync (`GET /sync`) dan push notification
- Panel admin Filament

**Belum dibangun, jangan dibuat dulu:** perjalanan dan checklist persiapan (v0.2), tanggal penting (v0.2), kenangan dan foto (v0.3), ekspor data dan hapus akun (v1.0), tugas berulang (v1.0), penarikan tabungan (v1.x), realtime (v1.x).

**Belum final:** bagian "Perubahan dari rancangan awal" dan "Masih terbuka" di akhir kontrak belum semuanya disetujui pemilik. Kalau ada yang ditolak, kontrak dan file ini diperbarui.

## Stack

- PHP 8.4+ (paket yang terkunci di `composer.lock` butuh minimal 8.4.1), Laravel 13 tanpa starter kit, MySQL 8.4 LTS
- Sanctum personal access token (dari `php artisan install:api`), satu token per HP
- Filament 5 di `/admin`
- Pest untuk tes, Laravel Pint untuk format
- Queue driver `database`, scheduler setiap menit
- Push: FCM HTTP v1 lewat `kreait/laravel-firebase`
- File: disk `r2` (driver `s3`, butuh `league/flysystem-aws-s3-v3`) ke Cloudflare R2, akses lewat URL bertanda tangan
- Intervention Image untuk mengolah foto
- Scramble untuk dokumentasi API di `/docs/api`
- Deploy: Coolify dengan build pack Dockerfile, otomatis saat merge ke `main`. Image-nya `dunglas/frankenphp` (mode klasik, tanpa Octane), dan satu container menjalankan tiga proses lewat supervisor: FrankenPHP untuk web, `php artisan queue:work`, dan `php artisan schedule:work`

## Perintah

- `composer run setup` — sekali di awal: pasang dependensi, buat `.env`, generate key, migrate, build aset
- `composer run dev` — server lokal, queue listener, dan Vite sekaligus
- `php artisan serve --host=0.0.0.0` — kalau HP perlu mengakses lewat Wi-Fi yang sama
- `php artisan test` — semua tes (Pest)
- `php artisan test --compact tests/Feature/NamaTest.php` atau `php artisan test --compact --filter="nama tes"` — satu file atau satu tes
- `./vendor/bin/pint` — format kode, jalankan sebelum commit
- `php artisan migrate:fresh --seed` — reset database lokal lalu isi admin dan data contoh (jangan pernah di staging/produksi)
- `php artisan reminders:send` — kirim pengingat yang jatuh tempo sekarang; di server dijalankan scheduler tiap menit

## Konfigurasi (`.env`)

- `APP_TIMEZONE=UTC`, `APP_LOCALE=id`, `DB_CONNECTION=mysql`, `QUEUE_CONNECTION=database`
- `APP_TIMEZONE` selain `UTC` ditolak saat aplikasi mulai (`AppServiceProvider`), juga di server. Zona waktu yang dilihat pengguna diatur per akun dan per ruang (bawaan `Asia/Jakarta`).
- `ADMIN_EMAIL` — satu-satunya email yang boleh masuk `/admin`
- `FIREBASE_CREDENTIALS` — path ke file service account Firebase (di luar repo atau di folder yang di-gitignore), atau isi JSON-nya langsung dalam satu baris, yang lebih mudah di Coolify
- `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_BUCKET`, `R2_ENDPOINT` — disk `r2`, region `auto`, bucket privat
- Setiap variabel baru ditambahkan juga ke `.env.example` dengan nilai kosong atau contoh.

## Struktur

- Route: `routes/api.php`, semua di bawah prefix `v1` (jadi `/api/v1/...`)
- Controller: `app/Http/Controllers/Api/V1/`
- Validasi: Form Request di `app/Http/Requests/`
- Output: API Resource di `app/Http/Resources/`
- Akses: Policy per model di `app/Policies/` (cek keanggotaan ruang)
- Logika bersama (sync, undangan, pengingat, total tabungan): `app/Actions/`. Action yang butuh pelaku menerima `User $actor` dan memeriksa aksesnya dengan `Gate::forUser($actor)`, bukan user yang sedang login, supaya bisa dipanggil dari seeder dan job.
- Job antrean (push, hitung ulang pengingat): `app/Jobs/`. Foto profil diolah langsung di request, sesuai kontrak.
- Perintah terjadwal: `routes/console.php`
- Deploy: `Dockerfile`, `.dockerignore`, dan folder `docker/` (pengaturan PHP produksi, konfigurasi supervisor, skrip start)
- Enum: `app/Enums/`; aturan validasi sendiri: `app/Rules/`; pembentuk error: `app/Exceptions/`
- Admin: `app/Filament/`
- Model daftar tugas bernama `TaskList`, bukan `List` (kata kunci PHP)

## Aturan wajib

### ID dan waktu

- Semua primary key ULID huruf kecil: trait `HasUlids`, `$table->ulid('id')->primary()`, relasi `foreignUlid()`.
- Migrasi bawaan yang merujuk user ikut ULID: tabel token Sanctum dan `notifications` memakai `ulidMorphs()`, `sessions.user_id` juga ULID.
- ULID huruf besar dari request ditolak 422 (jangan diam-diam diubah ke huruf kecil). Rule `ulid` bawaan Laravel menerima huruf besar maupun kecil, jadi penolakan ini butuh aturan tambahan.
- Semua tabel: `$table->timestamps(6)`; tabel konten juga `$table->softDeletes(precision: 6)`. Model memakai trait `App\Models\Concerns\HasMicrosecondTimestamps`, yang menetapkan format `Y-m-d H:i:s.u` lewat `getDateFormat()`. Properti `$dateFormat` tidak bisa ditaruh di trait karena PHP menolaknya sebagai bentrok dengan properti bawaan `Model`. `tests/Unit/ArchTest.php` mewajibkan trait itu dan `HasUlids` di semua model.
- Resource memformat tanggal `Y-m-d` dan jam `H:i` secara eksplisit; waktu lengkap memakai format bawaan Laravel (ISO 8601 dengan mikrodetik, akhiran `Z`).
- Waktu dari HP wajib membawa `Z` atau offset; tanpa itu 422.
- `created_at`, `updated_at`, `deleted_at` hanya diisi server.

### Uang

- Simpan sebagai `BIGINT` dalam satuan terkecil, plus `currency` (kode ISO 4217). Jangan pernah float atau decimal untuk uang.
- Desimal per mata uang: IDR 0, JPY 0, USD/SGD/MYR/EUR 2. Simpan sebagai konstanta di `config/currencies.php`, jangan ambil dari `NumberFormatter`.
- Nominal lebih dari 0, paling besar 10^15. `currency` target hanya bisa diisi saat dibuat.

### Menulis data

- `PUT /<sumber>/{id}` membuat data dengan ID dari HP → 201. Kalau ID sudah ada di ruang yang sama: 200 dengan data tersimpan, tanpa mengubah apa pun. Kalau sudah terhapus: 410 `resource_deleted`. Kalau ID dipakai di ruang lain: 409 `id_conflict`.
- `PATCH` hanya mengubah kolom yang dikirim; kolom hanya-baca dan tak dikenal diabaikan. Mengosongkan `due_date` ikut mengosongkan `due_time`.
- `DELETE` = soft delete, 204, aman diulang.
- Penghapusan berantai (daftar ke tugas, target ke setoran) harus ikut memperbarui `updated_at` anak-anaknya. Pakai query Eloquent, jangan `DB::table()->update()`.
- Mengubah `assignee_ids` wajib `touch()` tugasnya (pivot tidak punya kursor sendiri).
- Tulis ke daftar/target yang terhapus: 409 `parent_deleted`; ke daftar yang diarsipkan: 409 `parent_archived`. Validasi induk dengan `whereNull('deleted_at')`, bukan rule `exists` biasa.
- Daftar aktif terakhir di sebuah ruang tidak boleh dihapus atau diarsipkan: 409 `last_list`. Kunci baris ruang (`lockForUpdate()`) saat menghitung.
- Data di ruang yang bukan ruang user dianggap tidak ada: 404, bukan 403.

### Error

- Bentuk JSON: `message`, `code`, `errors` (validasi), dan `current` (endpoint yang lewat antrean HP). Dibuat terpusat di `bootstrap/app.php` lewat `withExceptions()`, plus `shouldRenderJsonWhen()` untuk `api/*`.
- `current` = resource di URL request (route model, atau user untuk `/me`); `null` kalau ID itu belum ada.
- `not_found` hanya untuk `ModelNotFoundException`. Route yang tidak ada: 404 `route_not_found`.
- Salah email/kata sandi: 422 `invalid_credentials`, bukan 401. 401 hanya untuk token tidak berlaku.
- Pesan berbahasa Indonesia dari paket `laravel-lang`. Daftar lengkap `code` ada di bagian Error pada kontrak; jangan menambah kode baru tanpa memperbarui kontrak.

### Sync (`GET /sync`)

- Koleksi: `spaces` (dengan `members`), `task_lists`, `tasks`, `savings_goals`, `savings_entries`.
- Tiap koleksi diurutkan `(updated_at, id)`, paling banyak 500 baris per halaman; `next_cursor` menyimpan posisi tiap koleksi.
- `server_time` dicatat saat halaman pertama dan sama di semua halaman. Mundur 5 detik dari `since`, sekali, di halaman pertama.
- Tanpa `since`: hanya data aktif. Dengan `since`: termasuk yang terhapus.
- `cursor` yang bukan buatan server dibalas 422 `validation_failed`. Kalau `cursor` dikirim, `since` tidak dibaca.
- Isi Ruang Pribadi tidak pernah dikirim ke anggota lain. Wajib ada tesnya.
- Perubahan anggota atau profil anggota (nama, warna, foto) wajib `touch()` ruangnya.

### Ruang dan undangan

- Ruang Pribadi dibuat saat daftar, berisi satu daftar "Tugas". Ruang Bersama dibuat saat kode undangan diterima, berisi tepat 2 orang; satu akun paling banyak satu Ruang Bersama.
- Kode: 6 karakter dari `23456789ABCDEFGHJKLMNPQRSTUVWXYZ`, berlaku 48 jam, sekali pakai. Input diseragamkan (huruf besar, tanpa spasi/tanda hubung).
- Terima kode: kunci baris kedua akun (urut ID) lalu baris undangan, dalam satu transaksi. Urutan cek: sudah satu ruang dengan pembuat kode → 200; punya Ruang Bersama dengan orang lain → 409 `shared_space_exists`; masalah apa pun pada kode → 422 `invitation_invalid` dengan pesan yang sama.

### Notifikasi dan pengingat

- Push dikirim dari job antrean ke semua token penerima, hanya jika `notification_prefs` penerima menyala. Semua nilai `data` FCM berupa string.
- Tipe: `task_reminder`, `task_assigned`, `task_completed`, `savings_deposit`, `savings_achieved`, `partner_joined`. Penerima, channel, dan prioritasnya ada di kontrak.
- `remind_at` (UTC) dihitung dari `due_date` + `due_time` di zona waktu ruang; tanpa jam → 08.00. Diklaim dengan `UPDATE ... WHERE reminder_sent_at IS NULL` tanpa menyentuh `updated_at`. Scheduler pakai `withoutOverlapping()`. Telat lebih dari 30 menit: tandai terkirim tanpa push.
- Pengingat tugas yang selesai, terhapus, atau ada di daftar arsip dibiarkan terbuka, jadi masih terkirim kalau tugasnya kembali dalam 30 menit itu; setelahnya ikut ditandai terkirim.
- Token yang ditolak FCM (`UNREGISTERED`) dihapus dari `device_tokens`.
- `savings_achieved` paling banyak sekali per target (`achieved_notified_at`); total dihitung dalam transaksi yang mengunci baris target.

## Admin (Filament)

- Panel admin di `/admin`, login session biasa (terpisah dari token Sanctum). `User::canAccessPanel()` hanya mengizinkan `ADMIN_EMAIL`.
- Resource v0.1: Pengguna (lihat; aksi "Reset kata sandi" yang sekaligus mencabut semua token), Ruang dan anggotanya (lihat saja), Undangan (lihat, cabut), Token perangkat (lihat, hapus).
- Jangan buat resource yang menampilkan isi tugas, daftar, atau tabungan pengguna; itu privasi pengguna.

## Data lokal

- `DemoSeeder` (hanya lokal, dipanggil juga oleh `DatabaseSeeder`): dua akun `aku@contoh.test` dan `pasangan@contoh.test` (kata sandi `password`), satu Ruang Bersama, beberapa daftar, tugas, target, dan setoran. Buat ruangnya lewat Action yang sama dengan endpoint terima undangan. Kalau akun contoh sudah ada, seeder ini tidak membuat apa-apa.
- `AdminUserSeeder` (hanya lokal, dipanggil juga oleh `DatabaseSeeder`): membuat user dengan email `ADMIN_EMAIL` dan kata sandi `password`, atau mengatur ulang kata sandinya kalau user itu sudah ada. Seeder ini menolak jalan di luar lingkungan `local` dan `testing`; di server, admin dibuat dengan `php artisan make:filament-user`.

## Tes

- Setiap endpoint punya feature test Pest: berhasil, validasi, akses ke ruang lain (404), dan `PUT` yang diulang.
- Tes sync wajib mencakup: banyak baris dengan `updated_at` sama, data terhapus, dan kebocoran Ruang Pribadi.
- Tes memakai MySQL (database `*_testing` di `phpunit.xml`), bukan SQLite, supaya perilaku waktu dan JSON sama dengan produksi. Database `todolist` dan `todolist_testing` harus sudah dibuat di MySQL lokal.
- Ekspektasi Pest `toBeUlid()` hanya menerima huruf besar. Untuk ULID proyek ini pakai `toMatch('/^[0-7][0-9a-hjkmnp-tv-z]{25}$/')`.
- Tes tidak boleh menyentuh FCM asli: `tests/Pest.php` membuat `Messaging` melempar error saat dibuat. Tes yang memicu push ke penerima yang punya perangkat memakai `Queue::fake([SendPushNotification::class])` atau mock `Messaging`.

## Git dan deploy

- Kerja di branch (`feat/...`, `fix/...`), lalu merge ke `main`. Merge ke `main` langsung men-deploy ke Coolify, jadi tes harus hijau dulu.
- Commit kecil per perubahan, pesan singkat dengan awalan `feat:`, `fix:`, `chore:`, `test:`, atau `docs:`.
- Migration yang sudah sampai staging/produksi tidak diedit lagi; buat migration baru.
- Setiap container mulai, `docker/start-container` menjalankan `php artisan migrate --force` lalu `php artisan optimize`, baru menyalakan ketiga proses. Kalau migrasi gagal, container berhenti dan Coolify mempertahankan versi sebelumnya.
- Di Coolify: Ports Exposes `80`, health check memakai `/up` dari `HEALTHCHECK` di Dockerfile, dan log aplikasi tampil di tab Logs karena `LOG_CHANNEL=stderr`.
- Variabel yang wajib diisi di Coolify: `APP_KEY`, `APP_URL`, `APP_LOCALE=id`, `DB_*`, dan `ADMIN_EMAIL`. Kredensial R2 dan Firebase baru diperlukan saat foto profil dan push dipakai.
- `bootstrap/app.php` memercayai semua proxy, karena di produksi aplikasi hanya bisa dijangkau lewat proxy Coolify. Jangan membuka port container langsung ke internet.
- Domain produksi lewat Cloudflare lebih dulu, baru proxy Coolify. Akibatnya IP yang terbaca aplikasi adalah IP Cloudflare, jadi batas request per IP (daftar, masuk, terima undangan) belum dihitung per pengguna. Rapikan sebelum rilis publik.
- Akun admin di server dibuat dengan `php artisan make:filament-user` di Terminal Coolify. Kata sandinya diganti lewat menu Pengguna, aksi "Reset kata sandi".

## Jangan

- Jangan mengubah bentuk request atau response tanpa memperbarui `docs/kontrak-api.md`.
- Jangan commit `.env`, file service account Firebase, atau kunci R2.
- Jangan menjalankan `migrate:fresh` atau seeder di staging/produksi.
- Jangan menambah package baru tanpa alasan yang jelas; pakai yang sudah ada di Stack.

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.4. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

</laravel-boost-guidelines>
