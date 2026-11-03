<?php

use App\Enums\VerificationPurpose;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

it('emails a code to reset the password and answers 202 without a body', function () {
    $this->travelTo('2026-11-02 10:00:00');
    $user = User::factory()->create(['email' => 'nama@email.com']);

    $response = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'Nama@Email.com']);

    $response->assertNoContent(202);
    $code = sentVerificationCode('nama@email.com', VerificationPurpose::PasswordReset);
    $stored = VerificationCode::query()->sole();
    expect($code)->toMatch('/^\d{6}$/')
        ->and($stored->user_id)->toBe($user->id)
        ->and($stored->purpose)->toBe(VerificationPurpose::PasswordReset)
        ->and($stored->code_hash)->toBe(VerificationCode::hash($code))
        ->and($stored->attempts)->toBe(0)
        ->and($stored->expires_at->format('Y-m-d H:i:s'))->toBe('2026-11-02 11:00:00');
});

it('answers 202 the same way for an email without an account, and sends nothing', function () {
    $response = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'tidak-ada@email.com']);

    $response->assertNoContent(202);
    Mail::assertNothingQueued();
    $this->assertDatabaseCount('verification_codes', 0);
});

it('replaces the previous code with a new one that works for another 60 minutes', function () {
    $this->travelTo('2026-11-02 10:00:00');
    User::factory()->create(['email' => 'nama@email.com']);
    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nama@email.com']);
    VerificationCode::query()->update(['attempts' => 3]);
    $this->travelTo('2026-11-02 10:20:00');

    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nama@email.com'])->assertNoContent(202);

    $stored = VerificationCode::query()->sole();
    expect($stored->code_hash)->toBe(VerificationCode::hash(sentVerificationCode('nama@email.com', VerificationPurpose::PasswordReset)))
        ->and($stored->attempts)->toBe(0)
        ->and($stored->expires_at->format('Y-m-d H:i:s'))->toBe('2026-11-02 11:20:00');
    Mail::assertQueuedCount(2);
});

it('keeps the code for verifying the email apart', function () {
    $user = User::factory()->unverified()->create(['email' => 'nama@email.com']);
    $verification = VerificationCode::factory()->for($user)->forEmailVerification()->create();

    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nama@email.com'])->assertNoContent(202);

    $this->assertModelExists($verification);
    expect(VerificationCode::query()->count())->toBe(2);
});

it('queues the email to the name of the account once the code is saved', function () {
    User::factory()->create(['email' => 'nama@email.com', 'name' => 'Iqbal']);

    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nama@email.com']);

    Mail::assertQueued(VerificationCodeMail::class, fn (VerificationCodeMail $mail) => $mail->afterCommit === true && $mail->recipientName === 'Iqbal');
});

it('returns 422 for an email that is missing or not an email', function (array $body) {
    $response = $this->postJson('/api/v1/auth/forgot-password', $body);

    $response->assertUnprocessable()
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonValidationErrors(['email']);
})->with([
    'missing' => [[]],
    'not an email' => [['email' => 'bukan-email']],
    'not text' => [['email' => ['nama@email.com']]],
]);

it('allows 3 requests per 15 minutes for one email', function () {
    $this->travelTo('2026-11-02 10:00:00');
    User::factory()->create(['email' => 'nama@email.com']);

    foreach (range(1, 3) as $request) {
        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nama@email.com'])->assertNoContent(202);
    }

    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'NAMA@email.com'])
        ->assertTooManyRequests()
        ->assertJsonPath('code', 'too_many_requests');
    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'lain@email.com'])->assertNoContent(202);

    $this->travelTo('2026-11-02 10:15:01');
    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nama@email.com'])->assertNoContent(202);
});

it('allows 10 requests per hour from one IP address', function () {
    foreach (range(1, 10) as $request) {
        $this->postJson('/api/v1/auth/forgot-password', ['email' => "orang{$request}@email.com"])->assertNoContent(202);
    }

    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'orang11@email.com'])->assertTooManyRequests();
});
