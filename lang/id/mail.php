<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email Texts
    |--------------------------------------------------------------------------
    |
    | The texts of the emails with a code, from the API contract. The owner
    | approved the first ones on 2 November 2026 and these, longer and in an
    | HTML layout, on 3 November 2026.
    |
    */

    'greeting' => 'Halo :name,',
    'greeting_without_name' => 'Halo,',
    'validity' => 'Kode ini berlaku selama :minutes menit dan hanya bisa dipakai sekali. Demi keamanan akunmu, jangan berikan kode ini kepada siapa pun, termasuk orang yang mengaku dari Denly Days.',
    'closing' => 'Salam hangat,',
    'signature' => 'Denly Days',
    'automated' => 'Email ini dikirim otomatis, jadi balasan ke alamat ini tidak dibaca.',
    'help' => 'Butuh bantuan? Hubungi :email.',

    'password_reset' => [
        'subject' => 'Kode atur ulang kata sandi Denly Days',
        'intro' => 'Kami menerima permintaan untuk mengatur ulang kata sandi akun Denly Days yang memakai email ini. Masukkan kode berikut di app untuk membuat kata sandi baru:',
        'outro' => 'Kalau kamu tidak meminta pengaturan ulang kata sandi, abaikan saja email ini. Kata sandimu tidak berubah dan akunmu tetap aman.',
    ],

    'email_verification' => [
        'subject' => 'Kode verifikasi email Denly Days',
        'intro' => 'Terima kasih sudah memakai Denly Days. Untuk memastikan email ini benar milikmu, masukkan kode berikut di app:',
        'outro' => 'Kalau kamu tidak membuat akun Denly Days, abaikan saja email ini.',
    ],

];
