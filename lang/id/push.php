<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Push Notification Texts
    |--------------------------------------------------------------------------
    |
    | The texts of the push notifications. The reminder and the
    | partner_joined texts come from the API contract. The owner approved
    | the others as they are written here on 11 October 2026, the
    | special_date_reminder, trip_reminder, and savings_reminder texts on
    | 22 October 2026, the memory_created text on 23 October 2026, the
    | savings_withdrawal, comment_created, and reaction_created texts on
    | 29 October 2026, and the partner_left text and the partner_joined text
    | for a restored space on 30 October 2026.
    |
    */

    'task_reminder' => [
        'with_time' => 'Tenggat hari ini, :time',
        'without_time' => 'Tenggat hari ini',
    ],

    'task_assigned' => [
        'body' => ':name menitipkan tugas ini ke kamu',
    ],

    'task_completed' => [
        'body' => ':name menyelesaikan tugas ini',
    ],

    'savings_deposit' => [
        'body' => ':name menabung :amount',
    ],

    'savings_achieved' => [
        'body' => 'Target tabungan ini tercapai',
    ],

    'savings_withdrawal' => [
        'body' => ':name mengambil :amount',
    ],

    'partner_joined' => [
        'body' => ':name sudah bergabung. Ruang Bersama kalian siap.',
        'restored_body' => ':name sudah bergabung lagi. Isi Ruang Bersama kalian kembali utuh.',
    ],

    'partner_left' => [
        'body' => ':name keluar dari Ruang Bersama. Isinya disimpan 30 hari kalau kalian ingin bergabung lagi.',
    ],

    'special_date_reminder' => [
        'title' => 'Tanggal penting',
        'in_a_week' => ':title 7 hari lagi',
        'tomorrow' => ':title besok',
        'today' => ':title hari ini',
        'birthday' => 'Ulang tahun :name',
    ],

    'trip_reminder' => [
        'title' => 'Perjalanan',
        'in_a_week' => ':title 7 hari lagi',
        'tomorrow' => ':title besok',
        'with_progress' => ':reminder. Persiapan :done dari :total beres.',
    ],

    'savings_reminder' => [
        'body' => 'Waktunya menabung. Terkumpul :percent% dari target.',
    ],

    'memory_created' => [
        'body' => ':name menyimpan kenangan baru',
    ],

    'comment_created' => [
        'body' => ':name: :comment',
    ],

    'reaction_created' => [
        'body' => ':name memberi reaksi :emoji',
    ],

];
