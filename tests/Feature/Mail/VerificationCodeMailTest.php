<?php

use App\Enums\VerificationPurpose;
use App\Mail\VerificationCodeMail;
use Illuminate\Contracts\Queue\ShouldQueue;

it('writes the code and how long it works in plain text, in the words of the contract', function (VerificationPurpose $purpose, string $subject, string $intro, string $outro) {
    $mail = new VerificationCodeMail($purpose, '482913');

    $mail->assertHasSubject($subject);
    $mail->assertSeeInOrderInText([$intro, $outro]);
    expect($mail->render())->toBe("{$intro}\n\n{$outro}\n");
})->with([
    'to reset the password' => [
        VerificationPurpose::PasswordReset,
        'Kode atur ulang kata sandi Denly Days',
        'Kode untuk mengatur ulang kata sandi Denly Days: 482913.',
        'Kode ini berlaku 60 menit. Kalau kamu tidak memintanya, abaikan email ini; kata sandimu tidak berubah.',
    ],
    'to verify the email' => [
        VerificationPurpose::EmailVerification,
        'Kode verifikasi email Denly Days',
        'Kode untuk memverifikasi email akun Denly Days: 482913.',
        'Kode ini berlaku 60 menit. Kalau kamu tidak membuat akun Denly Days, abaikan email ini.',
    ],
]);

it('is sent from the queue once the transaction that saved the code commits', function () {
    $mail = new VerificationCodeMail(VerificationPurpose::PasswordReset, '482913');

    expect($mail)->toBeInstanceOf(ShouldQueue::class)
        ->and($mail->afterCommit)->toBeTrue();
});
