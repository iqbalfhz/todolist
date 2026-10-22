<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Push Notification Texts
    |--------------------------------------------------------------------------
    |
    | The texts of the six push notifications. The reminder and the
    | partner_joined texts come from the API contract. The owner approved
    | the others as they are written here on 11 October 2026, and the
    | special_date_reminder, trip_reminder, and savings_reminder texts on
    | 22 October 2026.
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

    'partner_joined' => [
        'body' => ':name sudah bergabung. Ruang Bersama kalian siap.',
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

];
