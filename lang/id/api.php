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
        'shared_space_exists' => 'Kamu sudah punya Ruang Bersama.',
        'resource_deleted' => 'Data ini sudah dihapus.',
        'validation_failed' => 'Data yang dikirim tidak valid.',
        'invalid_credentials' => 'Email atau kata sandi salah.',
        'invitation_invalid' => 'Kode tidak berlaku, minta kode baru.',
        'too_many_requests' => 'Terlalu sering. Coba lagi sebentar lagi.',
        'server_error' => 'Terjadi kesalahan di server. Coba lagi nanti.',
    ],

    'parent_deleted' => [
        'task_list' => 'Daftar ini sudah dihapus.',
        'savings_goal' => 'Target ini sudah dihapus.',
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
        'timezone' => 'zona waktu',
        'color' => 'warna',
        'default_currency' => 'mata uang',
        'birth_date' => 'tanggal lahir',
        'notification_prefs' => 'preferensi notifikasi',
        'notification_prefs.task_reminders' => 'pengingat tugas',
        'notification_prefs.partner_activity' => 'aktivitas pasangan',
        'notification_prefs.savings_activity' => 'aktivitas tabungan',
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
        'since' => 'since',
        'cursor' => 'cursor',
    ],

];
