<?php

use App\Enums\AccountNotice;
use App\Mail\AccountNoticeMail;
use Illuminate\Contracts\Queue\ShouldQueue;

it('writes the email change notice in the words of the contract, with the new email masked', function () {
    config(['admin.support_email' => 'bantuan@contoh.test']);
    $mail = new AccountNoticeMail(AccountNotice::EmailChanged, 'Iqbal', 'ba***@gmail.com');

    $mail->assertHasSubject('Email akun Denly Days kamu sudah diganti');
    $mail->assertSeeInOrderInText([
        'Halo Iqbal,',
        'Email akun Denly Days kamu baru saja diganti dari alamat ini ke ba***@gmail.com. Mulai sekarang, gunakan email baru itu untuk masuk dan untuk mengatur ulang kata sandi.',
        'Kalau bukan kamu yang menggantinya, segera hubungi bantuan@contoh.test supaya kami bisa membantu mengamankan akunmu.',
        "Salam hangat,\nDenly Days",
        'Email ini dikirim otomatis, jadi balasan ke alamat ini tidak dibaca.',
        'Butuh bantuan? Hubungi bantuan@contoh.test.',
    ]);
    $mail->assertSeeInOrderInHtml([
        '<title>Email akun Denly Days kamu sudah diganti</title>',
        'Halo Iqbal,',
        'Email akun Denly Days kamu baru saja diganti dari alamat ini ke ba***@gmail.com.',
        'Kalau bukan kamu yang menggantinya, segera hubungi <a href="mailto:bantuan@contoh.test" style="color:#B42A5E;">bantuan@contoh.test</a> supaya kami bisa membantu mengamankan akunmu.',
        'Salam hangat,<br>Denly Days',
    ], false);
});

it('writes the password change notice in the words of the contract', function () {
    config(['admin.support_email' => 'bantuan@contoh.test']);
    $mail = new AccountNoticeMail(AccountNotice::PasswordChanged, 'Iqbal');

    $mail->assertHasSubject('Kata sandi akun Denly Days kamu sudah diubah');
    $mail->assertSeeInOrderInText([
        'Halo Iqbal,',
        'Kata sandi akun Denly Days kamu baru saja diubah lewat app, dan akunmu sudah dikeluarkan dari HP lain.',
        'Kalau bukan kamu yang mengubahnya, segera atur ulang kata sandi lewat Lupa kata sandi di app, lalu hubungi bantuan@contoh.test.',
        "Salam hangat,\nDenly Days",
    ]);
    $mail->assertSeeInHtml('lalu hubungi <a href="mailto:bantuan@contoh.test" style="color:#B42A5E;">bantuan@contoh.test</a>.', false);
});

it('leaves the warning out of the email change notice while no support address is set', function () {
    config(['admin.support_email' => null]);
    $mail = new AccountNoticeMail(AccountNotice::EmailChanged, 'Iqbal', 'ba***@gmail.com');

    $mail->assertSeeInText('Email akun Denly Days kamu baru saja diganti');
    $mail->assertDontSeeInText('Kalau bukan kamu');
    $mail->assertDontSeeInHtml('Kalau bukan kamu');
    $mail->assertDontSeeInText('Butuh bantuan?');
    $mail->assertDontSeeInHtml('mailto:', false);
});

it('keeps only the advice to reset the password while no support address is set', function () {
    config(['admin.support_email' => null]);
    $mail = new AccountNoticeMail(AccountNotice::PasswordChanged, 'Iqbal');

    $mail->assertSeeInText('Kalau bukan kamu yang mengubahnya, segera atur ulang kata sandi lewat Lupa kata sandi di app.');
    $mail->assertSeeInHtml('Kalau bukan kamu yang mengubahnya, segera atur ulang kata sandi lewat Lupa kata sandi di app.</p>', false);
    $mail->assertDontSeeInText('hubungi');
    $mail->assertDontSeeInHtml('mailto:', false);
});

it('escapes the name and the masked email in the HTML version', function () {
    $mail = new AccountNoticeMail(AccountNotice::EmailChanged, '<b>Iqbal</b>', '<i>***@gmail.com');

    $mail->assertSeeInHtml('Halo &lt;b&gt;Iqbal&lt;/b&gt;,', false);
    $mail->assertSeeInHtml('ke &lt;i&gt;***@gmail.com.', false);
    $mail->assertDontSeeInHtml('<b>Iqbal</b>', false);
    $mail->assertDontSeeInHtml('<i>', false);
});

it('is sent from the queue once the transaction that made the change commits', function () {
    $mail = new AccountNoticeMail(AccountNotice::PasswordChanged, 'Iqbal');

    expect($mail)->toBeInstanceOf(ShouldQueue::class)
        ->and($mail->afterCommit)->toBeTrue();
});
