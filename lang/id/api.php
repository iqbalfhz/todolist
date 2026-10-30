<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Error Messages
    |--------------------------------------------------------------------------
    |
    | The default message for each error code in the API contract. The app
    | shows these messages as they are, so they are written for end users.
    |
    */

    'errors' => [
        'unauthenticated' => 'Sesi kamu sudah berakhir. Silakan masuk lagi.',
        'forbidden' => 'Kamu tidak boleh melakukan ini.',
        'not_found' => 'Data tidak ditemukan.',
        'route_not_found' => 'Alamat ini tidak dikenal. Coba perbarui aplikasi.',
        'parent_deleted' => 'Induk data ini sudah dihapus.',
        'parent_archived' => 'Daftar ini sudah diarsipkan.',
        'last_list' => 'Setiap ruang harus punya minimal satu daftar aktif.',
        'id_conflict' => 'ID ini sudah dipakai data lain.',
        'trip_checklist' => 'Checklist ini ikut perjalanannya. Hapus perjalanannya kalau ingin menghapus checklist.',
        'shared_space_exists' => 'Kamu sudah punya Ruang Bersama.',
        'resource_deleted' => 'Data ini sudah dihapus.',
        'validation_failed' => 'Data yang dikirim tidak valid.',
        'invalid_credentials' => 'Email atau kata sandi salah.',
        'invitation_invalid' => 'Kode tidak berlaku, minta kode baru.',
        'google_token_invalid' => 'Masuk dengan Google gagal. Coba lagi.',
        'too_many_requests' => 'Terlalu sering. Coba lagi sebentar lagi.',
        'places_unavailable' => 'Pencarian tempat sedang tidak tersedia. Pin tetap bisa dipasang langsung di peta.',
        'server_error' => 'Terjadi kesalahan di server. Coba lagi nanti.',
    ],

    'google' => [
        'email_unverified' => 'Email akun Google ini belum terverifikasi.',
    ],

    'parent_deleted' => [
        'task_list' => 'Daftar ini sudah dihapus.',
        'savings_goal' => 'Target ini sudah dihapus.',
        'trip' => 'Perjalanan ini sudah dihapus.',
        'memory' => 'Kenangan ini sudah dihapus.',
        'task' => 'Tugas ini sudah dihapus.',
        'savings_entry' => 'Setoran ini sudah dihapus.',
        'savings_withdrawal' => 'Penarikan ini sudah dihapus.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Messages
    |--------------------------------------------------------------------------
    |
    | Messages for the validation rules that belong to this application.
    |
    */

    'validation' => [
        'lowercase_ulid' => ':Attribute harus berupa ULID huruf kecil.',
        'iso8601_with_offset' => ':Attribute harus berupa waktu ISO 8601 dengan Z atau offset.',
        'birth_date' => ':Attribute harus berformat YYYY-MM-DD atau --MM-DD dan berupa tanggal yang ada.',
        'due_time_needs_due_date' => 'Jam tenggat butuh tanggal tenggat.',
        'assignee_not_member' => 'Penanggung jawab harus anggota ruang ini.',
        'space_not_member' => 'Ruang ini tidak ditemukan.',
        'different_space' => 'Daftar tujuan harus berada di ruang yang sama.',
        'sync_cursor' => ':Attribute tidak berlaku, mulai sinkron dari awal.',
        'end_date_needs_start_date' => 'Tanggal selesai butuh tanggal mulai.',
        'end_date_before_start_date' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.',
        'savings_goal_different_space' => 'Target tabungan harus berada di ruang yang sama dengan perjalanannya.',
        'trip_different_space' => 'Perjalanan harus berada di ruang yang sama dengan daftarnya.',
        'trip_has_checklist' => 'Perjalanan ini sudah punya checklist.',
        'reminder_day_needed' => 'Hari setor wajib diisi kalau pengingat setor dinyalakan.',
        'reminder_day_weekly' => 'Hari setor mingguan harus antara 1 (Senin) dan 7 (Minggu).',
        'reminder_day_monthly' => 'Tanggal setor bulanan harus antara 1 dan 31.',
        'memory_trip_different_space' => 'Perjalanan harus berada di ruang yang sama dengan kenangannya.',
        'memory_photo_limit' => 'Satu kenangan paling banyak :max foto.',
        'repeat_needs_due_date' => 'Tugas berulang butuh tanggal tenggat.',
        'repeat_in_trip_checklist' => 'Checklist perjalanan tidak bisa berulang.',
        'position' => ':Attribute harus berupa angka antara -10^15 dan 10^15.',
        'discussion_needs_shared_space' => 'Komentar dan reaksi hanya ada di Ruang Bersama.',
        'personal_space_cannot_be_closed' => 'Ruang Pribadi tidak bisa dihapus.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Attribute Names
    |--------------------------------------------------------------------------
    |
    | The names validation messages use for each request field.
    |
    */

    'attributes' => [
        'id' => 'ID',
        'name' => 'nama',
        'email' => 'email',
        'password' => 'kata sandi',
        'device_name' => 'nama perangkat',
        'id_token' => 'token Google',
        'timezone' => 'zona waktu',
        'color' => 'warna',
        'default_currency' => 'mata uang',
        'birth_date' => 'tanggal lahir',
        'notification_prefs' => 'preferensi notifikasi',
        'notification_prefs.task_reminders' => 'pengingat tugas',
        'notification_prefs.partner_activity' => 'aktivitas pasangan',
        'notification_prefs.savings_activity' => 'aktivitas tabungan',
        'notification_prefs.special_dates' => 'pengingat tanggal penting',
        'notification_prefs.trip_reminders' => 'pengingat perjalanan',
        'notification_prefs.savings_reminders' => 'pengingat setor tabungan',
        'photo' => 'foto',
        'token' => 'token',
        'fcm_token' => 'token FCM',
        'platform' => 'platform',
        'code' => 'kode',
        'renew' => 'pembaruan kode',
        'space_id' => 'ruang',
        'emoji' => 'emoji',
        'position' => 'posisi',
        'archived_at' => 'waktu arsip',
        'task_list_id' => 'daftar',
        'title' => 'judul',
        'notes' => 'catatan',
        'due_date' => 'tenggat',
        'due_time' => 'jam tenggat',
        'repeat_frequency' => 'pengulangan',
        'repeat_interval' => 'jarak pengulangan',
        'priority' => 'prioritas',
        'assignee_ids' => 'penanggung jawab',
        'assignee_ids.*' => 'penanggung jawab',
        'completed_at' => 'waktu selesai',
        'target_amount' => 'nominal target',
        'currency' => 'mata uang',
        'target_date' => 'tanggal target',
        'savings_goal_id' => 'target',
        'amount' => 'nominal',
        'entry_date' => 'tanggal setor',
        'note' => 'catatan',
        'type' => 'jenis',
        'date' => 'tanggal',
        'destination' => 'tujuan',
        'start_date' => 'tanggal mulai',
        'end_date' => 'tanggal selesai',
        'trip_id' => 'perjalanan',
        'reminder_frequency' => 'pengingat setor',
        'reminder_day' => 'hari setor',
        'place_name' => 'nama tempat',
        'latitude' => 'garis lintang',
        'longitude' => 'garis bujur',
        'q' => 'kata kunci',
        'visited_on' => 'tanggal',
        'story' => 'cerita',
        'memory_id' => 'kenangan',
        'taken_at' => 'waktu foto',
        'subject_type' => 'jenis subjek',
        'subject_id' => 'subjek',
        'body' => 'komentar',
        'since' => 'since',
        'cursor' => 'cursor',
    ],

];
