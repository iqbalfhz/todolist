<?php

use App\Enums\VerificationPurpose;
use App\Models\User;
use App\Models\VerificationCode;
use Database\Factories\VerificationCodeFactory;
use Illuminate\Support\Facades\Mail;

/**
 * Sign in a registered user whose email is not verified yet.
 */
function actingAsUnverifiedUser(): User
{
    $user = User::factory()->unverified()->withPersonalSpace()->create(['email' => 'nama@email.com']);

    test()->actingAs($user, 'sanctum');

    return $user;
}

describe('store', function () {
    it('emails a code to verify the email and answers 202 without a body', function () {
        Mail::fake();
        $this->travelTo('2026-11-02 10:00:00');
        $user = actingAsUnverifiedUser();

        $response = $this->postJson('/api/v1/me/email-verification');

        $response->assertNoContent(202);
        $code = sentVerificationCode('nama@email.com', VerificationPurpose::EmailVerification);
        $stored = VerificationCode::query()->sole();
        expect($code)->toMatch('/^\d{6}$/')
            ->and($stored->user_id)->toBe($user->id)
            ->and($stored->purpose)->toBe(VerificationPurpose::EmailVerification)
            ->and($stored->code_hash)->toBe(VerificationCode::hash($code))
            ->and($stored->expires_at->format('Y-m-d H:i:s'))->toBe('2026-11-02 11:00:00');
    });

    it('answers 204 and sends nothing when the email is verified already', function () {
        Mail::fake();
        actingAsApiUser();

        $response = $this->postJson('/api/v1/me/email-verification');

        $response->assertNoContent();
        Mail::assertNothingQueued();
        $this->assertDatabaseCount('verification_codes', 0);
    });

    it('allows 3 requests per 15 minutes for one account', function () {
        Mail::fake();
        $this->travelTo('2026-11-02 10:00:00');
        actingAsUnverifiedUser();

        foreach (range(1, 3) as $request) {
            $this->postJson('/api/v1/me/email-verification')->assertNoContent(202);
        }

        $this->postJson('/api/v1/me/email-verification')->assertTooManyRequests();

        $this->travelTo('2026-11-02 10:15:01');
        $this->postJson('/api/v1/me/email-verification')->assertNoContent(202);
    });

    it('returns 401 when no token is sent', function () {
        $this->postJson('/api/v1/me/email-verification')->assertUnauthorized();
    });
});

describe('confirm', function () {
    it('verifies the email with the code and returns the profile', function () {
        $this->travelTo('2026-11-02 10:00:00.250000');
        $user = actingAsUnverifiedUser();
        $code = VerificationCode::factory()->for($user)->forEmailVerification()->create();

        $response = $this->postJson('/api/v1/me/email-verification/confirm', ['code' => VerificationCodeFactory::CODE]);

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email_verified_at', '2026-11-02T10:00:00.250000Z');
        $this->assertModelMissing($code);
    });

    it('verifies the email with the code from the email that was sent', function () {
        Mail::fake();
        actingAsUnverifiedUser();
        $this->postJson('/api/v1/me/email-verification');

        $response = $this->postJson('/api/v1/me/email-verification/confirm', [
            'code' => sentVerificationCode('nama@email.com', VerificationPurpose::EmailVerification),
        ]);

        $response->assertOk()->assertJsonPath('data.email', 'nama@email.com');
        expect($response->json('data.email_verified_at'))->not->toBeNull();
    });

    it('returns 422 for a wrong code, counts the try, and leaves the email unverified', function () {
        $user = actingAsUnverifiedUser();
        $code = VerificationCode::factory()->for($user)->forEmailVerification()->create();

        $response = $this->postJson('/api/v1/me/email-verification/confirm', ['code' => '000000']);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonPath('errors.code.0', 'Kode salah atau sudah tidak berlaku. Minta kode baru kalau perlu.')
            ->assertJsonMissingPath('current');
        expect($code->fresh()->attempts)->toBe(1)
            ->and($user->fresh()->email_verified_at)->toBeNull();
    });

    it('ends the code after five wrong tries', function () {
        $user = actingAsUnverifiedUser();
        $code = VerificationCode::factory()->for($user)->forEmailVerification()->create();

        foreach (range(1, 5) as $try) {
            $this->postJson('/api/v1/me/email-verification/confirm', ['code' => '000000'])->assertUnprocessable();
        }

        $this->assertModelMissing($code);
        $this->postJson('/api/v1/me/email-verification/confirm', ['code' => VerificationCodeFactory::CODE])->assertUnprocessable();
        expect($user->fresh()->email_verified_at)->toBeNull();
    });

    it('returns 422 for a code that expired, and removes it', function () {
        $user = actingAsUnverifiedUser();
        $code = VerificationCode::factory()->for($user)->forEmailVerification()->expired()->create();

        $this->postJson('/api/v1/me/email-verification/confirm', ['code' => VerificationCodeFactory::CODE])->assertUnprocessable();

        $this->assertModelMissing($code);
    });

    it('does not accept a code for resetting the password', function () {
        $user = actingAsUnverifiedUser();
        $reset = VerificationCode::factory()->for($user)->create();

        $this->postJson('/api/v1/me/email-verification/confirm', ['code' => VerificationCodeFactory::CODE])->assertUnprocessable();

        expect($reset->fresh()->attempts)->toBe(0)
            ->and($user->fresh()->email_verified_at)->toBeNull();
    });

    it('returns the profile of an email that is verified already, whatever the code', function () {
        $this->travelTo('2026-10-11 08:00:00');
        actingAsApiUser();

        $response = $this->postJson('/api/v1/me/email-verification/confirm', ['code' => '000000']);

        $response->assertOk()->assertJsonPath('data.email_verified_at', '2026-10-11T08:00:00.000000Z');
    });

    it('returns 422 for a code that is missing or not 6 digits of text, without using up a try', function (array $body) {
        $user = actingAsUnverifiedUser();
        $code = VerificationCode::factory()->for($user)->forEmailVerification()->create();

        $response = $this->postJson('/api/v1/me/email-verification/confirm', $body);

        $response->assertUnprocessable()->assertJsonValidationErrors(['code']);
        expect($code->fresh()->attempts)->toBe(0);
    })->with([
        'missing' => [[]],
        '5 digits' => [['code' => '48291']],
        'letters' => [['code' => 'abcdef']],
        'sent as a number' => [['code' => (int) VerificationCodeFactory::CODE]],
    ]);

    it('allows 10 tries per minute for one account', function () {
        actingAsUnverifiedUser();

        foreach (range(1, 10) as $try) {
            $this->postJson('/api/v1/me/email-verification/confirm', ['code' => '000000'])->assertUnprocessable();
        }

        $this->postJson('/api/v1/me/email-verification/confirm', ['code' => VerificationCodeFactory::CODE])->assertTooManyRequests();
    });

    it('returns 401 when no token is sent', function () {
        $this->postJson('/api/v1/me/email-verification/confirm', ['code' => '000000'])->assertUnauthorized();
    });
});
