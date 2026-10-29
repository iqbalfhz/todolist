<?php

namespace App\Actions;

use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;

/**
 * Looks up places through the public Nominatim server of OpenStreetMap.
 *
 * Its usage policy allows at most one request per second for the whole app,
 * asks for caching and an identifying User-Agent, and forbids autocomplete:
 * https://operations.osmfoundation.org/policies/nominatim/
 */
class SearchPlaces
{
    /**
     * The most places one search returns.
     */
    public const MAX_RESULTS = 8;

    /**
     * How long, in seconds, a search waits for its turn before it gives up.
     */
    private const WAIT_SECONDS = 2;

    /**
     * The shortest gap, in milliseconds, between two requests to Nominatim.
     */
    private const GAP_MILLISECONDS = 1000;

    /**
     * Find the places that match the words, from the cache when they were searched in the last day.
     *
     * @return list<array{name: string, address: string, latitude: float, longitude: float}>
     */
    public function handle(string $words): array
    {
        if (! config('services.nominatim.enabled')) {
            throw new ApiException(ErrorCode::PlacesUnavailable);
        }

        $query = Str::of($words)->squish()->lower()->value();

        return Cache::remember('places:'.hash('sha256', $query), now()->addDay(), fn (): array => $this->fetch($query));
    }

    /**
     * Ask Nominatim, one request at a time and at most one per second.
     *
     * @return list<array{name: string, address: string, latitude: float, longitude: float}>
     */
    private function fetch(string $query): array
    {
        try {
            return Cache::lock('nominatim', 10)->block(self::WAIT_SECONDS, function () use ($query): array {
                $this->waitForTurn();

                try {
                    return $this->places($this->request($query));
                } finally {
                    Cache::put('nominatim:last-request', now()->valueOf(), 60);
                }
            });
        } catch (LockTimeoutException) {
            throw new ThrottleRequestsException(headers: ['Retry-After' => 1]);
        }
    }

    /**
     * Wait until a second has passed since the previous request to Nominatim.
     */
    private function waitForTurn(): void
    {
        $lastRequest = Cache::get('nominatim:last-request');

        if ($lastRequest === null) {
            return;
        }

        $wait = self::GAP_MILLISECONDS - (now()->valueOf() - (int) $lastRequest);

        if ($wait > 0) {
            Sleep::for($wait)->milliseconds();
        }
    }

    /**
     * Send the search. Only the words go out, nothing about the account.
     */
    private function request(string $query): Response
    {
        try {
            return Http::baseUrl(config('services.nominatim.url'))
                ->withUserAgent(config('services.nominatim.user_agent'))
                ->acceptJson()
                ->timeout(5)
                ->get('/search', [
                    'q' => $query,
                    'format' => 'jsonv2',
                    'limit' => self::MAX_RESULTS,
                    'accept-language' => 'id',
                ])
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('Nominatim did not answer a place search.', [
                'status' => $exception instanceof RequestException ? $exception->response->status() : null,
                'error' => $exception->getMessage(),
            ]);

            throw new ApiException(ErrorCode::PlacesUnavailable);
        }
    }

    /**
     * Turn the answer into the places the app shows, skipping any entry without coordinates.
     *
     * @return list<array{name: string, address: string, latitude: float, longitude: float}>
     */
    private function places(Response $response): array
    {
        $results = $response->json();

        return collect(is_array($results) ? $results : [])
            ->filter(fn (mixed $place): bool => is_array($place)
                && is_numeric($place['lat'] ?? null)
                && is_numeric($place['lon'] ?? null))
            ->take(self::MAX_RESULTS)
            ->map(function (array $place): array {
                $address = is_string($place['display_name'] ?? null) ? $place['display_name'] : '';
                $name = is_string($place['name'] ?? null) && $place['name'] !== '' ? $place['name'] : Str::before($address, ',');

                return [
                    'name' => $name,
                    'address' => $address,
                    'latitude' => round((float) $place['lat'], 6),
                    'longitude' => round((float) $place['lon'], 6),
                ];
            })
            ->values()
            ->all();
    }
}
