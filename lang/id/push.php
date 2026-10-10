<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Push Notification Texts
    |--------------------------------------------------------------------------
    |
    | The texts of the six push notifications. Only the reminder and the
    | partner_joined texts come from the API contract. The others are
    | provisional until the "Bahasa dan microcopy" section is copied here.
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

];
