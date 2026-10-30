<?php

namespace App\Actions;

use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use DomainException;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use stdClass;
use UnexpectedValueException;

class VerifyGoogleIdToken
{
    /**
     * Where Google publishes the public keys that sign its ID tokens.
     */
    public const KEYS_URL = 'https://www.googleapis.com/oauth2/v3/certs';

    /**
     * The issuers Google writes in its ID tokens.
     */
    private const ISSUERS = ['accounts.google.com', 'https://accounts.google.com'];

    /**
     * How far the clocks of Google and this server may differ, in seconds.
     */
    private const CLOCK_SKEW_SECONDS = 60;

    /**
     * How long the keys stay cached when Google gives no lifetime, in seconds.
     */
    private const DEFAULT_KEYS_LIFETIME_SECONDS = 3600;

    /**
     * The cache key of the keys Google published last.
     */
    private const KEYS_CACHE_KEY = 'google-id-token:keys';

    /**
     * The cache key that allows fetching the keys again early at most once a minute.
     */
    private const REFRESH_CACHE_KEY = 'google-id-token:refreshed';

    /**
     * Check an ID token from Sign in with Google and return the Google account it names.
     *
     * The token must be signed with one of Google's current keys, be issued
     * by Google for the Web client of this app, be within its lifetime, and
     * carry an email that Google verified.
     *
     * @return array{sub: string, email: string, name: string|null}
     */
    public function handle(string $idToken): array
    {
        $clientId = config('services.google.client_id');

        if (! is_string($clientId) || $clientId === '') {
            throw new RuntimeException('GOOGLE_CLIENT_ID is not set, so ID tokens from Google cannot be checked.');
        }

        $payload = $this->decode($idToken);

        $isForThisApp = in_array($payload->iss ?? null, self::ISSUERS, true)
            && ($payload->aud ?? null) === $clientId
            && is_numeric($payload->exp ?? null)
            && is_string($payload->sub ?? null) && $payload->sub !== '' && strlen($payload->sub) <= 255
            && is_string($payload->email ?? null) && filter_var($payload->email, FILTER_VALIDATE_EMAIL) !== false;

        if (! $isForThisApp) {
            throw new ApiException(ErrorCode::GoogleTokenInvalid);
        }

        if (! in_array($payload->email_verified ?? null, [true, 'true'], true)) {
            throw new ApiException(ErrorCode::GoogleTokenInvalid, __('api.google.email_unverified'));
        }

        return [
            'sub' => $payload->sub,
            'email' => Str::lower($payload->email),
            'name' => is_string($payload->name ?? null) ? $payload->name : null,
        ];
    }

    /**
     * Check the signature and the lifetime of the token, and return its claims.
     *
     * A key ID that the cached keys do not know means Google may have rotated
     * its keys, so they are fetched again, at most once a minute.
     */
    private function decode(string $idToken): stdClass
    {
        $keyId = $this->keyIdOf($idToken);
        [$keys, $fresh] = $this->keys();

        if (! array_key_exists($keyId, $keys) && ! $fresh && Cache::add(self::REFRESH_CACHE_KEY, true, 60)) {
            [$keys] = $this->keys(refresh: true);
        }

        $previousTimestamp = JWT::$timestamp;
        $previousLeeway = JWT::$leeway;
        JWT::$timestamp = now()->getTimestamp();
        JWT::$leeway = self::CLOCK_SKEW_SECONDS;

        try {
            return JWT::decode($idToken, $keys);
        } catch (UnexpectedValueException|DomainException|InvalidArgumentException) {
            throw new ApiException(ErrorCode::GoogleTokenInvalid);
        } finally {
            JWT::$timestamp = $previousTimestamp;
            JWT::$leeway = $previousLeeway;
        }
    }

    /**
     * Read the ID of the key that signed the token, refusing a header Google never writes.
     *
     * Checking the header here keeps odd values, such as an algorithm that is
     * not a string, from reaching the JWT library.
     */
    private function keyIdOf(string $idToken): string
    {
        $segments = explode('.', $idToken);
        $header = count($segments) === 3
            ? json_decode((string) base64_decode(strtr($segments[0], '-_', '+/')), true)
            : null;

        if (! is_array($header) || ($header['alg'] ?? null) !== 'RS256' || ! is_string($header['kid'] ?? null) || $header['kid'] === '') {
            throw new ApiException(ErrorCode::GoogleTokenInvalid);
        }

        return $header['kid'];
    }

    /**
     * Get Google's signing keys, from the cache unless asked to fetch them again.
     *
     * @return array{0: array<string, Key>, 1: bool} The keys by ID, and whether they were just fetched.
     */
    private function keys(bool $refresh = false): array
    {
        $jwks = $refresh ? null : Cache::get(self::KEYS_CACHE_KEY);
        $fresh = ! is_array($jwks);

        if ($fresh) {
            $jwks = $this->fetchKeys();
        }

        try {
            return [JWK::parseKeySet($jwks), $fresh];
        } catch (UnexpectedValueException|DomainException|InvalidArgumentException $exception) {
            Cache::forget(self::KEYS_CACHE_KEY);
            Log::warning('Google signing keys could not be read.', ['message' => $exception->getMessage()]);

            abort(503);
        }
    }

    /**
     * Download Google's signing keys and cache them for as long as Google allows.
     *
     * Without them no token can be checked, so the request fails with 503 and
     * the user can try again.
     *
     * @return array<array-key, mixed>
     */
    private function fetchKeys(): array
    {
        try {
            $response = Http::acceptJson()->timeout(5)->get(self::KEYS_URL)->throw();
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('Google signing keys could not be fetched.', ['message' => $exception->getMessage()]);

            abort(503);
        }

        $jwks = $response->json();

        if (! is_array($jwks)) {
            Log::warning('Google signing keys came back in an unknown shape.');

            abort(503);
        }

        Cache::put(self::KEYS_CACHE_KEY, $jwks, $this->lifetimeOf($response));

        return $jwks;
    }

    /**
     * Get how long Google allows the keys to be cached, from the max-age of the response.
     */
    private function lifetimeOf(Response $response): int
    {
        $maxAge = preg_match('/max-age=(\d+)/', $response->header('Cache-Control'), $matches) === 1
            ? (int) $matches[1]
            : self::DEFAULT_KEYS_LIFETIME_SECONDS;

        return max(60, min($maxAge, 86400));
    }
}
