<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email Texts
    |--------------------------------------------------------------------------
    |
    | The texts of the emails, from the API contract. The owner approved the
    | first code emails on 2 November 2026; these, longer and in an HTML
    | layout, on 3 November 2026; and the email change code and the two
    | account notices later that day.
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

    'email_change' => [
        'subject' => 'Kode ganti email Denly Days',
        'intro' => 'Kami menerima permintaan untuk mengganti email akun Denly Days kamu ke alamat ini. Masukkan kode berikut di app untuk menyelesaikannya:',
        'outro' => 'Kalau kamu tidak meminta penggantian email, abaikan saja email ini. Email akunmu tidak berubah.',
    ],

    'email_changed' => [
        'subject' => 'Email akun Denly Days kamu sudah diganti',
        'body' => 'Email akun Denly Days kamu baru saja diganti dari alamat ini ke :email. Mulai sekarang, gunakan email baru itu untuk masuk dan untuk mengatur ulang kata sandi.',
        'warning' => 'Kalau bukan kamu yang menggantinya, segera hubungi :support supaya kami bisa membantu mengamankan akunmu.',
    ],

    'password_changed' => [
        'subject' => 'Kata sandi akun Denly Days kamu sudah diubah',
        'body' => 'Kata sandi akun Denly Days kamu baru saja diubah lewat app, dan akunmu sudah dikeluarkan dari HP lain.',
        'warning' => 'Kalau bukan kamu yang mengubahnya, segera atur ulang kata sandi lewat Lupa kata sandi di app, lalu hubungi :support.',
        'warning_without_support' => 'Kalau bukan kamu yang mengubahnya, segera atur ulang kata sandi lewat Lupa kata sandi di app.',
    ],

];
