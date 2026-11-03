<?php

use App\Enums\AccountNotice;
use App\Enums\VerificationPurpose;
use App\Mail\AccountNoticeMail;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Models\VerificationCode;
use Database\Factories\VerificationCodeFactory;
use Illuminate\Support\Facades\Mail;

/**
 * Sign in a registered user whose email is the one the tests change from.
 */
function actingAsUserWithEmail(array $attributes = []): User
{
    $user = User::factory()->withPersonalSpace()->create(['email' => 'lama@email.com', 'name' => 'Iqbal', ...$attributes]);

    test()->actingAs($user, 'sanctum');

    return $user;
}

describe('store', function () {
    it('emails a code to the new address and keeps the old email until the code comes back', function () {
        Mail::fake();
        $this->travelTo('2026-11-03 10:00:00');
        $user = actingAsUserWithEmail();

        $response = $this->postJson('/api/v1/me/email-change', ['email' => 'baru@email.com', 'password' => 'password']);

        $response->assertNoContent(202);
        $code = sentVerificationCode('baru@email.com', VerificationPurpose::EmailChange);
        $stored = VerificationCode::query()->sole();
        expect($code)->toMatch('/^\d{6}$/')
            ->and($stored->user_id)->toBe($user->id)
            ->and($stored->purpose)->toBe(VerificationPurpose::EmailChange)
            ->and($stored->new_email)->toBe('baru@email.com')
            ->and($stored->code_hash)->toBe(VerificationCode::hash($code))
            ->and($stored->expires_at->format('Y-m-d H:i:s'))->toBe('2026-11-03 11:00:00')
            ->and($user->fresh()->email)->toBe('lama@email.com');
        Mail::assertQueuedCount(1);
        Mail::assertQueued(VerificationCodeMail::class, fn (VerificationCodeMail $mail): bool => $mail->hasTo('baru@email.com')
            && ! $mail->hasTo('lama@email.com')
            && $mail->recipientName === 'Iqbal');
    });

    it('replaces the code and the new email that were asked for before', function () {
        Mail::fake();
        actingAsUserWithEmail();
        $this->postJson('/api/v1/me/email-change', ['email' => 'pertama@email.com', 'password' => 'password'])->assertNoContent(202);

        $this->postJson('/api/v1/me/email-change', ['email' => 'kedua@email.com', 'password' => 'password'])->assertNoContent(202);

        $stored = VerificationCode::query()->sole();
        expect($stored->new_email)->toBe('kedua@email.com')
            ->and($stored->code_hash)->toBe(VerificationCode::hash(sentVerificationCode('kedua@email.com', VerificationPurpose::EmailChange)));
    });

    it('confirms the owner of an account from Google with an ID token of its Google account', function () {
        Mail::fake();
        config(['services.google.client_id' => GOOGLE_TEST_CLIENT_ID]);
        fakeGoogleKeys();
        actingAsUserWithEmail(['google_id' => '110248495921238986420', 'password' => null]);

        $response = $this->postJson('/api/v1/me/email-change', ['email' => 'baru@email.com', 'id_token' => googleIdToken()]);

        $response->assertNoContent(202);
        expect(sentVerificationCode('baru@email.com', VerificationPurpose::EmailChange))->not->toBeNull();
    });

    it('returns 422 with the invalid_credentials code for a wrong password and sends nothing', function () {
        Mail::fake();
        actingAsUserWithEmail();

        $response = $this->postJson('/api/v1/me/email-change', ['email' => 'baru@email.com', 'password' => 'bukan-kata-sandinya']);

        $response->assertUnprocessable()->assertExactJson([
            'message' => 'Kata sandi salah.',
            'code' => 'invalid_credentials',
        ]);
        Mail::assertNothingQueued();
        $this->assertDatabaseCount('verification_codes', 0);
    });

    it('returns 422 with the google_token_invalid code for a token of a Google account that is not linked', function () {
        Mail::fake();
        config(['services.google.client_id' => GOOGLE_TEST_CLIENT_ID]);
        fakeGoogleKeys();
        actingAsUserWithEmail(['google_id' => '209876543210987654321']);

        $response = $this->postJson('/api/v1/me/email-change', ['email' => 'baru@email.com', 'id_token' => googleIdToken()]);

        $response->assertUnprocessable()->assertJsonPath('code', 'google_token_invalid');
        Mail::assertNothingQueued();
    });

    it('returns 422 at the email for an address it cannot change to, before checking the password', function (Closure $email, string $message) {
        Mail::fake();
        actingAsUserWithEmail();

        $response = $this->postJson('/api/v1/me/email-change', ['email' => $email(), 'password' => 'bukan-kata-sandinya']);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonPath('errors.email', [$message]);
        Mail::assertNothingQueued();
    })->with([
        'the same email in other letters' => [fn () => 'LAMA@email.com', 'Email baru sama dengan email sekarang.'],
        'the email of another account' => [fn () => User::factory()->create(['email' => 'dipakai@email.com'])->email, 'Email ini sudah dipakai akun lain.'],
        'not an email' => [fn () => 'bukan-email', 'Email harus berupa alamat surel yang valid.'],
        'longer than 255 characters' => [fn () => str_repeat('a', 246).'@email.com', 'Email maksimal berisi 255 karakter.'],
    ]);

    it('returns 422 at the email for the admin account, which keeps its email', function () {
        Mail::fake();
        config(['admin.email' => 'lama@email.com']);
        actingAsUserWithEmail();

        $response = $this->postJson('/api/v1/me/email-change', ['email' => 'baru@email.com', 'password' => 'password']);

        $response->assertUnprocessable()->assertJsonPath('errors.email', ['Email akun admin hanya bisa diganti lewat server.']);
        Mail::assertNothingQueued();
    });

    it('returns 422 with the validation_failed code when neither the password nor a Google token is sent', function () {
        actingAsUserWithEmail();

        $response = $this->postJson('/api/v1/me/email-change', ['email' => 'baru@email.com']);

        $response->assertUnprocessable()->assertJsonPath('errors.password.0', 'Kata sandi wajib diisi bila tidak terdapat token Google.');
    });

    it('allows 3 requests per 15 minutes for one account', function () {
        Mail::fake();
        $this->travelTo('2026-11-03 10:00:00');
        actingAsUserWithEmail();

        foreach (range(1, 3) as $request) {
            $this->postJson('/api/v1/me/email-change', ['email' => 'baru@email.com', 'password' => 'password'])->assertNoContent(202);
        }

        $this->postJson('/api/v1/me/email-change', ['email' => 'baru@email.com', 'password' => 'password'])->assertTooManyRequests();

        $this->travelTo('2026-11-03 10:14:59');
        $this->postJson('/api/v1/me/email-change', ['email' => 'baru@email.com', 'password' => 'password'])->assertTooManyRequests();

        $this->travelTo('2026-11-03 10:15:01');
        $this->postJson('/api/v1/me/email-change', ['email' => 'baru@email.com', 'password' => 'password'])->assertNoContent(202);
    });

    it('returns 401 when no token is sent', function () {
        $this->postJson('/api/v1/me/email-change', ['email' => 'baru@email.com', 'password' => 'password'])->assertUnauthorized();
    });
});

describe('confirm', function () {
    it('changes the email with the code, marks it verified, and tells the old address', function () {
        Mail::fake();
        $this->travelTo('2026-11-03 10:00:00.250000');
        $user = actingAsUserWithEmail(['email_verified_at' => null]);
        $code = VerificationCode::factory()->for($user)->forEmailChange('baru@email.com')->create();

        $response = $this->postJson('/api/v1/me/email-change/confirm', ['code' => VerificationCodeFactory::CODE]);

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', 'baru@email.com')
            ->assertJsonPath('data.email_verified_at', '2026-11-03T10:00:00.250000Z');
        $this->assertModelMissing($code);
        expect($user->fresh()->email)->toBe('baru@email.com');
        Mail::assertQueuedCount(1);
        Mail::assertQueued(AccountNoticeMail::class, fn (AccountNoticeMail $mail): bool => $mail->hasTo('lama@email.com')
            && ! $mail->hasTo('baru@email.com')
            && $mail->notice === AccountNotice::EmailChanged
            && $mail->recipientName === 'Iqbal'
            && $mail->maskedEmail === 'ba***@email.com');
    });

    it('changes the email with the code from the email that was sent, so the account signs in with it', function () {
        Mail::fake();
        actingAsUserWithEmail();
        $this->postJson('/api/v1/me/email-change', ['email' => 'baru@email.com', 'password' => 'password']);

        $this->postJson('/api/v1/me/email-change/confirm', [
            'code' => sentVerificationCode('baru@email.com', VerificationPurpose::EmailChange),
        ])->assertOk();

        $this->postJson('/api/v1/auth/login', ['email' => 'baru@email.com', 'password' => 'password', 'device_name' => 'Pixel 9'])->assertOk();
        $this->postJson('/api/v1/auth/login', ['email' => 'lama@email.com', 'password' => 'password', 'device_name' => 'Pixel 9'])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'invalid_credentials');
    });

    it('returns 422 at the code for a wrong code, counts the try, and keeps the email', function () {
        Mail::fake();
        $user = actingAsUserWithEmail();
        $code = VerificationCode::factory()->for($user)->forEmailChange()->create();

        $response = $this->postJson('/api/v1/me/email-change/confirm', ['code' => '000000']);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonPath('errors.code.0', 'Kode salah atau sudah tidak berlaku. Minta kode baru kalau perlu.');
        expect($code->fresh()->attempts)->toBe(1)
            ->and($user->fresh()->email)->toBe('lama@email.com');
        Mail::assertNothingQueued();
    });

    it('returns 422 at the code when no usable code to change the email is waiting', function (Closure $code) {
        $user = actingAsUserWithEmail();
        $code($user);

        $response = $this->postJson('/api/v1/me/email-change/confirm', ['code' => VerificationCodeFactory::CODE]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['code']);
        expect($user->fresh()->email)->toBe('lama@email.com');
    })->with([
        'no code at all' => [fn (User $user) => null],
        'a code that expired' => [fn (User $user) => VerificationCode::factory()->for($user)->forEmailChange()->expired()->create()],
        'a code to verify the email' => [fn (User $user) => VerificationCode::factory()->for($user)->forEmailVerification()->create()],
        'a code to reset the password' => [fn (User $user) => VerificationCode::factory()->for($user)->create()],
    ]);

    it('returns 422 at the email when another account took the new email in the meantime, and keeps the code', function () {
        Mail::fake();
        $user = actingAsUserWithEmail();
        $code = VerificationCode::factory()->for($user)->forEmailChange('baru@email.com')->create();
        User::factory()->create(['email' => 'BARU@email.com']);

        $response = $this->postJson('/api/v1/me/email-change/confirm', ['code' => VerificationCodeFactory::CODE]);

        $response->assertUnprocessable()->assertJsonPath('errors.email', ['Email ini sudah dipakai akun lain.']);
        $this->assertModelExists($code);
        expect($code->fresh()->attempts)->toBe(0)
            ->and($user->fresh()->email)->toBe('lama@email.com');
        Mail::assertNothingQueued();
    });

    it('returns 422 at the email for the admin account and keeps the code', function () {
        Mail::fake();
        config(['admin.email' => 'lama@email.com']);
        $user = actingAsUserWithEmail();
        $code = VerificationCode::factory()->for($user)->forEmailChange()->create();

        $response = $this->postJson('/api/v1/me/email-change/confirm', ['code' => VerificationCodeFactory::CODE]);

        $response->assertUnprocessable()->assertJsonPath('errors.email', ['Email akun admin hanya bisa diganti lewat server.']);
        $this->assertModelExists($code);
        expect($user->fresh()->email)->toBe('lama@email.com');
    });

    it('allows 10 tries per minute for one account', function () {
        actingAsUserWithEmail();

        foreach (range(1, 10) as $try) {
            $this->postJson('/api/v1/me/email-change/confirm', ['code' => '000000'])->assertUnprocessable();
        }

        $this->postJson('/api/v1/me/email-change/confirm', ['code' => VerificationCodeFactory::CODE])->assertTooManyRequests();
    });

    it('returns 401 when no token is sent', function () {
        $this->postJson('/api/v1/me/email-change/confirm', ['code' => '000000'])->assertUnauthorized();
    });
});
