<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email Texts
    |--------------------------------------------------------------------------
    |
    | The texts of the emails with a code, from the API contract. The owner
    | approved them on 2 November 2026.
    |
    */

    'password_reset' => [
        'subject' => 'Kode atur ulang kata sandi Denly Days',
        'intro' => 'Kode untuk mengatur ulang kata sandi Denly Days: :code.',
        'outro' => 'Kode ini berlaku :minutes menit. Kalau kamu tidak memintanya, abaikan email ini; kata sandimu tidak berubah.',
    ],

    'email_verification' => [
        'subject' => 'Kode verifikasi email Denly Days',
        'intro' => 'Kode untuk memverifikasi email akun Denly Days: :code.',
        'outro' => 'Kode ini berlaku :minutes menit. Kalau kamu tidak membuat akun Denly Days, abaikan email ini.',
    ],

];
