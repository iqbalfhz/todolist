<?php

use App\Actions\VerifyGoogleIdToken;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    config(['services.google.client_id' => GOOGLE_TEST_CLIENT_ID]);
});

/**
 * Encode a part of a hand-made token.
 *
 * @param  array<string, mixed>  $data
 */
function base64UrlJson(array $data): string
{
    return rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');
}

describe('store', function () {
    it('creates an account with a personal space for a new Google user and returns 201', function () {
        $this->travelTo('2026-10-30 09:00:00');
        fakeGoogleKeys();

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => googleIdToken(['email' => 'Chika@Gmail.com']),
            'device_name' => 'Redmi Note 14 Pro+ 5G',
            'timezone' => 'Asia/Makassar',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.name', 'Chika')
            ->assertJsonPath('data.user.email', 'chika@gmail.com')
            ->assertJsonPath('data.user.timezone', 'Asia/Makassar');
        $user = User::query()->where('email', 'chika@gmail.com')->sole();
        expect($user->google_id)->toBe('110248495921238986420')
            ->and($user->password)->toBeNull()
            ->and($user->email_verified_at->toJSON())->toBe('2026-10-30T09:00:00.000000Z')
            ->and($user->personalSpace()->taskLists()->pluck('name')->all())->toBe(['Tugas']);
        $this->withToken($response->json('data.token'))->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.id', $user->id);
    });

    it('signs in to the account linked to the Google account, even after its Google email changed', function () {
        fakeGoogleKeys();
        $user = User::factory()->withPersonalSpace()->create(['email' => 'chika@kantor.example', 'google_id' => '110248495921238986420']);

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => googleIdToken(['email' => 'chika.baru@gmail.com']),
            'device_name' => 'Redmi Note 14 Pro+ 5G',
        ]);

        $response->assertOk()->assertJsonPath('data.user.id', $user->id)->assertJsonPath('data.user.email', 'chika@kantor.example');
        $this->assertDatabaseCount('users', 1);
    });

    it('links the account registered with the same email, and its password keeps working', function () {
        fakeGoogleKeys();
        $user = User::factory()->withPersonalSpace()->create(['email' => 'chika@gmail.com', 'password' => 'kata-sandi-lama']);

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => googleIdToken(['email' => 'Chika@gmail.com']),
            'device_name' => 'Redmi Note 14 Pro+ 5G',
        ]);

        $response->assertOk()->assertJsonPath('data.user.id', $user->id);
        expect($user->fresh()->google_id)->toBe('110248495921238986420');
        $this->postJson('/api/v1/auth/login', [
            'email' => 'chika@gmail.com',
            'password' => 'kata-sandi-lama',
            'device_name' => 'Laptop',
        ])->assertOk();
    });

    it('prefers the account linked to the Google account over another with the same email', function () {
        fakeGoogleKeys();
        $linked = User::factory()->withPersonalSpace()->create(['email' => 'lama@contoh.test', 'google_id' => '110248495921238986420']);
        User::factory()->withPersonalSpace()->create(['email' => 'chika@gmail.com']);

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => googleIdToken(),
            'device_name' => 'Redmi Note 14 Pro+ 5G',
        ]);

        $response->assertOk()->assertJsonPath('data.user.id', $linked->id);
    });

    it('gives an existing account without a personal space one, as signing in does', function () {
        fakeGoogleKeys();
        $user = User::factory()->create(['email' => 'chika@gmail.com']);

        $this->postJson('/api/v1/auth/google', [
            'id_token' => googleIdToken(),
            'device_name' => 'Laptop',
        ])->assertOk();

        expect($user->personalSpace())->not->toBeNull();
    });

    it('names a new account after Google, or after the email when Google gives no name', function (?string $googleName, string $name) {
        fakeGoogleKeys();

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => googleIdToken(['name' => $googleName]),
            'device_name' => 'Redmi Note 14 Pro+ 5G',
        ]);

        $response->assertCreated()->assertJsonPath('data.user.name', $name);
    })->with([
        'no name from Google' => [null, 'chika'],
        'a name of spaces only' => ['   ', 'chika'],
        'a name with extra spaces' => ['  Chika   Ayu ', 'Chika Ayu'],
        'a name longer than 50 characters' => [str_repeat('a', 49).' bcd', str_repeat('a', 49)],
    ]);

    it('does not let an account made with Google sign in with a password', function () {
        fakeGoogleKeys();
        $this->postJson('/api/v1/auth/google', ['id_token' => googleIdToken(), 'device_name' => 'Redmi Note 14 Pro+ 5G'])->assertCreated();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'chika@gmail.com',
            'password' => 'tebakan-apa-saja',
            'device_name' => 'Laptop',
        ]);

        $response->assertUnprocessable()->assertJsonPath('code', 'invalid_credentials');
    });

    it('accepts a token issued a little ahead of the server clock', function () {
        fakeGoogleKeys();

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => googleIdToken(['iat' => now()->addSeconds(30)->getTimestamp()]),
            'device_name' => 'Redmi Note 14 Pro+ 5G',
        ]);

        $response->assertCreated();
    });

    it('returns 422 with the google_token_invalid code for a token that is not good for this app', function (Closure $token) {
        fakeGoogleKeys();

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => $token(),
            'device_name' => 'Redmi Note 14 Pro+ 5G',
        ]);

        $response->assertUnprocessable()->assertExactJson([
            'message' => 'Masuk dengan Google gagal. Coba lagi.',
            'code' => 'google_token_invalid',
        ]);
        $this->assertDatabaseEmpty('users');
    })->with([
        'a token for another app' => [fn () => googleIdToken(['aud' => '123-lain.apps.googleusercontent.com'])],
        'a token from another issuer' => [fn () => googleIdToken(['iss' => 'https://accounts.evil.example'])],
        'an expired token' => [fn () => googleIdToken(['iat' => now()->subHours(2)->getTimestamp(), 'exp' => now()->subMinutes(2)->getTimestamp()])],
        'a token issued in the future' => [fn () => googleIdToken(['iat' => now()->addMinutes(10)->getTimestamp(), 'exp' => now()->addMinutes(70)->getTimestamp()])],
        'a token without an expiry' => [fn () => googleIdToken(['exp' => null])],
        'a token signed with another key' => [fn () => googleIdToken(signedWith: 'google-key-lain')],
        'a token naming a key Google never published' => [fn () => googleIdToken(keyId: 'kunci-tidak-dikenal')],
        'a token without an email' => [fn () => googleIdToken(['email' => null])],
        'a token without a subject' => [fn () => googleIdToken(['sub' => null])],
        'a token that is not signed' => [fn () => base64UrlJson(['alg' => 'none', 'kid' => 'google-key-1']).'.'.base64UrlJson(['aud' => GOOGLE_TEST_CLIENT_ID, 'sub' => '1', 'email' => 'chika@gmail.com']).'.'],
        'a token signed with a shared secret' => [fn () => JWT::encode(['aud' => GOOGLE_TEST_CLIENT_ID, 'sub' => '1'], str_repeat('k', 64), 'HS256', 'google-key-1')],
        'a header with an odd algorithm' => [fn () => base64UrlJson(['alg' => ['RS256'], 'kid' => 'google-key-1']).'.e30.c2ln'],
        'text that is not a token' => [fn () => 'bukan-token'],
    ]);

    it('returns 422 with its own message when Google did not verify the email', function (mixed $verified) {
        fakeGoogleKeys();

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => googleIdToken(['email_verified' => $verified]),
            'device_name' => 'Redmi Note 14 Pro+ 5G',
        ]);

        $response->assertUnprocessable()->assertExactJson([
            'message' => 'Email akun Google ini belum terverifikasi.',
            'code' => 'google_token_invalid',
        ]);
    })->with([
        'marked as not verified' => [false],
        'not marked at all' => [null],
    ]);

    it('fetches Google\'s keys once and keeps them for as long as Google allows', function () {
        $this->travelTo('2026-10-30 09:00:00');
        fakeGoogleKeys(maxAge: 7200);
        $signIn = fn () => $this->postJson('/api/v1/auth/google', ['id_token' => googleIdToken(), 'device_name' => 'Redmi Note 14 Pro+ 5G']);

        $signIn()->assertCreated();
        $this->travelTo('2026-10-30 10:59:00');
        $signIn()->assertOk();
        Http::assertSentCount(1);
        $this->travelTo('2026-10-30 11:00:01');
        $signIn()->assertOk();
        Http::assertSentCount(2);
    });

    it('does not fetch the keys twice for an unknown key when they were just fetched', function () {
        fakeGoogleKeys();

        $response = $this->postJson('/api/v1/auth/google', ['id_token' => googleIdToken(keyId: 'kunci-tidak-dikenal'), 'device_name' => 'Redmi Note 14 Pro+ 5G']);

        $response->assertUnprocessable()->assertJsonPath('code', 'google_token_invalid');
        Http::assertSentCount(1);
    });

    it('fetches the keys again for a key it does not know yet, at most once a minute', function () {
        Http::fake([VerifyGoogleIdToken::KEYS_URL => Http::sequence()
            ->push(['keys' => [googleSigningKey('google-key-1')['jwk']]], 200, ['Cache-Control' => 'max-age=21600'])
            ->push(['keys' => [googleSigningKey('google-key-1')['jwk'], googleSigningKey('google-key-2')['jwk']]], 200, ['Cache-Control' => 'max-age=21600'])
            ->push(['keys' => [googleSigningKey('google-key-1')['jwk'], googleSigningKey('google-key-2')['jwk'], googleSigningKey('google-key-3')['jwk']]], 200, ['Cache-Control' => 'max-age=21600'])]);
        $signIn = fn (string $keyId) => $this->postJson('/api/v1/auth/google', ['id_token' => googleIdToken(keyId: $keyId), 'device_name' => 'Redmi Note 14 Pro+ 5G']);

        $signIn('google-key-1')->assertCreated();
        $signIn('google-key-2')->assertOk();
        $signIn('google-key-3')->assertUnprocessable();
        Http::assertSentCount(2);
        $this->travel(61)->seconds();
        $signIn('google-key-3')->assertOk();
        Http::assertSentCount(3);
    });

    it('returns 503 and logs a warning when Google\'s keys cannot be fetched, and tries again next time', function (Closure $failure) {
        Log::spy();
        Http::fake([VerifyGoogleIdToken::KEYS_URL => Http::sequence()
            ->pushResponse($failure())
            ->push(['keys' => [googleSigningKey('google-key-1')['jwk']]], 200, ['Cache-Control' => 'max-age=21600'])]);
        $signIn = fn () => $this->postJson('/api/v1/auth/google', ['id_token' => googleIdToken(), 'device_name' => 'Redmi Note 14 Pro+ 5G']);

        $signIn()->assertServiceUnavailable()->assertJsonPath('code', 'server_error');

        Log::shouldHaveReceived('warning')->with('Google signing keys could not be fetched.', Mockery::type('array'))->once();
        $signIn()->assertCreated();
    })->with([
        'an error status' => [fn () => Http::response('Bad Gateway', 502)],
        'no connection' => [fn () => Http::failedConnection()],
    ]);

    it('returns 503 when Google answers with keys it cannot read', function () {
        Log::spy();
        Http::fake([VerifyGoogleIdToken::KEYS_URL => Http::response(['keys' => []], 200, ['Cache-Control' => 'max-age=21600'])]);

        $response = $this->postJson('/api/v1/auth/google', ['id_token' => googleIdToken(), 'device_name' => 'Redmi Note 14 Pro+ 5G']);

        $response->assertServiceUnavailable()->assertJsonPath('code', 'server_error');
        Log::shouldHaveReceived('warning')->with('Google signing keys could not be read.', Mockery::type('array'))->once();
    });

    it('returns 500 and reports it when the server has no Google client ID', function () {
        Http::fake();
        config(['services.google.client_id' => null]);

        $response = $this->postJson('/api/v1/auth/google', ['id_token' => googleIdToken(), 'device_name' => 'Redmi Note 14 Pro+ 5G']);

        $response->assertInternalServerError()->assertJsonPath('code', 'server_error');
        Exceptions::assertReported(RuntimeException::class);
        Http::assertNothingSent();
    });

    it('returns 422 for missing or invalid fields', function (array $payload, string $field) {
        Http::fake();

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => 'eyJhbGciOiJSUzI1NiJ9.e30.c2ln',
            'device_name' => 'Redmi Note 14 Pro+ 5G',
            ...$payload,
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonValidationErrors([$field]);
        Http::assertNothingSent();
    })->with([
        'no token' => [['id_token' => null], 'id_token'],
        'a token longer than 4,096 characters' => [['id_token' => str_repeat('a', 4097)], 'id_token'],
        'no device name' => [['device_name' => null], 'device_name'],
        'an unknown timezone' => [['timezone' => 'Asia/Bandung'], 'timezone'],
    ]);

    it('allows 5 attempts a minute per IP', function () {
        Http::fake();
        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/v1/auth/google', ['id_token' => 'bukan-token', 'device_name' => 'HP'])->assertUnprocessable();
        }

        $response = $this->postJson('/api/v1/auth/google', ['id_token' => 'bukan-token', 'device_name' => 'HP']);

        $response->assertTooManyRequests()->assertJsonPath('code', 'too_many_requests');
    });
});
