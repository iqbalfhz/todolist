<?php

use App\Enums\VerificationPurpose;
use App\Mail\VerificationCodeMail;
use Illuminate\Contracts\Queue\ShouldQueue;

dataset('purposes', [
    'to reset the password' => [
        VerificationPurpose::PasswordReset,
        'Kode atur ulang kata sandi Denly Days',
        'Kami menerima permintaan untuk mengatur ulang kata sandi akun Denly Days yang memakai email ini. Masukkan kode berikut di app untuk membuat kata sandi baru:',
        'Kalau kamu tidak meminta pengaturan ulang kata sandi, abaikan saja email ini. Kata sandimu tidak berubah dan akunmu tetap aman.',
    ],
    'to verify the email' => [
        VerificationPurpose::EmailVerification,
        'Kode verifikasi email Denly Days',
        'Terima kasih sudah memakai Denly Days. Untuk memastikan email ini benar milikmu, masukkan kode berikut di app:',
        'Kalau kamu tidak membuat akun Denly Days, abaikan saja email ini.',
    ],
    'to change the email' => [
        VerificationPurpose::EmailChange,
        'Kode ganti email Denly Days',
        'Kami menerima permintaan untuk mengganti email akun Denly Days kamu ke alamat ini. Masukkan kode berikut di app untuk menyelesaikannya:',
        'Kalau kamu tidak meminta penggantian email, abaikan saja email ini. Email akunmu tidak berubah.',
    ],
]);

it('writes the plain text version in the words of the contract', function (VerificationPurpose $purpose, string $subject, string $intro, string $outro) {
    config(['admin.support_email' => 'bantuan@contoh.test']);
    $mail = new VerificationCodeMail($purpose, '482913', 'Iqbal');

    $mail->assertHasSubject($subject);
    $mail->assertSeeInOrderInText([
        'Halo Iqbal,',
        $intro,
        '482913',
        'Kode ini berlaku selama 60 menit dan hanya bisa dipakai sekali. Demi keamanan akunmu, jangan berikan kode ini kepada siapa pun, termasuk orang yang mengaku dari Denly Days.',
        $outro,
        "Salam hangat,\nDenly Days",
        'Email ini dikirim otomatis, jadi balasan ke alamat ini tidak dibaca.',
        'Butuh bantuan? Hubungi bantuan@contoh.test.',
    ]);
})->with('purposes');

it('lays out the HTML version with the same words, the code in its own box, and a link to the support address', function (VerificationPurpose $purpose, string $subject, string $intro, string $outro) {
    config(['admin.support_email' => 'bantuan@contoh.test']);
    $mail = new VerificationCodeMail($purpose, '482913', 'Iqbal');

    $mail->assertSeeInOrderInHtml([
        "<title>{$subject}</title>",
        'Denly Days',
        'Halo Iqbal,',
        $intro,
        'letter-spacing:8px; color:#CB2F69;">482913</span>',
        'Kode ini berlaku selama 60 menit dan hanya bisa dipakai sekali.',
        $outro,
        'Salam hangat,<br>Denly Days',
        'Email ini dikirim otomatis, jadi balasan ke alamat ini tidak dibaca.',
        'Butuh bantuan? Hubungi <a href="mailto:bantuan@contoh.test" style="color:#B42A5E;">bantuan@contoh.test</a>.',
    ], false);
})->with('purposes');

it('leaves the support sentence out while no support address is set', function () {
    config(['admin.support_email' => null]);
    $mail = new VerificationCodeMail(VerificationPurpose::PasswordReset, '482913', 'Iqbal');

    $mail->assertSeeInText('Email ini dikirim otomatis, jadi balasan ke alamat ini tidak dibaca.');
    $mail->assertDontSeeInText('Butuh bantuan?');
    $mail->assertDontSeeInHtml('Butuh bantuan?');
});

it('escapes the name in the HTML version', function () {
    $mail = new VerificationCodeMail(VerificationPurpose::PasswordReset, '482913', '<b>Iqbal</b>');

    $mail->assertSeeInHtml('Halo &lt;b&gt;Iqbal&lt;/b&gt;,', false);
    $mail->assertDontSeeInHtml('<b>Iqbal</b>', false);
});

it('greets without a name when the email was queued before names were sent', function () {
    $mail = new VerificationCodeMail(VerificationPurpose::PasswordReset, '482913');

    $mail->assertSeeInText("Halo,\n");
    $mail->assertSeeInHtml('<p style="margin:0 0 16px;">Halo,</p>', false);
});

it('is sent from the queue once the transaction that saved the code commits', function () {
    $mail = new VerificationCodeMail(VerificationPurpose::PasswordReset, '482913', 'Iqbal');

    expect($mail)->toBeInstanceOf(ShouldQueue::class)
        ->and($mail->afterCommit)->toBeTrue();
});
