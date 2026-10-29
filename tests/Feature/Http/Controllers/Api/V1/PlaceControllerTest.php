<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;

/**
 * Build one result of Nominatim's /search, with the fields the server reads.
 *
 * @return array<string, mixed>
 */
function nominatimPlace(string $name, string $displayName, string $latitude, string $longitude): array
{
    return [
        'place_id' => 1,
        'name' => $name,
        'display_name' => $displayName,
        'lat' => $latitude,
        'lon' => $longitude,
    ];
}

it('returns the places Nominatim finds, named from the address when the place has no name', function () {
    actingAsApiUser();
    Http::fake(['nominatim.openstreetmap.org/search*' => Http::response([
        nominatimPlace('Ubud', 'Ubud, Gianyar, Bali, Indonesia', '-8.5068536', '115.2624778'),
        nominatimPlace('', 'Jalan Monkey Forest, Ubud, Bali, Indonesia', '-8.5188', '115.2585'),
    ])]);

    $response = $this->getJson('/api/v1/places?q=Ubud');

    $response->assertOk()->assertExactJson(['data' => [
        ['name' => 'Ubud', 'address' => 'Ubud, Gianyar, Bali, Indonesia', 'latitude' => -8.506854, 'longitude' => 115.262478],
        ['name' => 'Jalan Monkey Forest', 'address' => 'Jalan Monkey Forest, Ubud, Bali, Indonesia', 'latitude' => -8.5188, 'longitude' => 115.2585],
    ]]);
});

it('returns at most 8 places and skips any without coordinates', function () {
    actingAsApiUser();
    Http::fake(['nominatim.openstreetmap.org/search*' => Http::response([
        ['name' => 'Tanpa titik', 'display_name' => 'Tanpa titik'],
        ...array_fill(0, 9, nominatimPlace('Kuta', 'Kuta, Badung, Bali, Indonesia', '-8.7238', '115.1752')),
    ])]);

    $response = $this->getJson('/api/v1/places?q=Kuta');

    $response->assertOk()->assertJsonCount(8, 'data')->assertJsonPath('data.0.name', 'Kuta');
});

it('sends only the words, once, with the User-Agent of the app', function () {
    actingAsApiUser();
    Http::fake(['nominatim.openstreetmap.org/search*' => Http::response([])]);

    $this->getJson('/api/v1/places?q='.urlencode('Ubud   Bali'));

    Http::assertSent(fn (Request $request): bool => str_starts_with($request->header('User-Agent')[0], 'DenlyDays/1.0 (+')
        && $request->data() === ['q' => 'ubud bali', 'format' => 'jsonv2', 'limit' => 8, 'accept-language' => 'id']);
});

it('answers the same words from the cache for a day, whatever their case and spacing', function () {
    actingAsApiUser();
    Http::fake(['nominatim.openstreetmap.org/search*' => Http::response([
        nominatimPlace('Ubud', 'Ubud, Gianyar, Bali, Indonesia', '-8.5068536', '115.2624778'),
    ])]);
    $this->getJson('/api/v1/places?q=Ubud');

    $response = $this->getJson('/api/v1/places?q='.urlencode('  UBUD '));

    $response->assertOk()->assertJsonPath('data.0.name', 'Ubud');
    Http::assertSentCount(1);
});

it('waits until a second has passed since the previous request to Nominatim', function () {
    actingAsApiUser();
    Http::fake(['nominatim.openstreetmap.org/search*' => Http::response([])]);
    $this->travelTo('2026-10-29 10:00:00');
    $this->getJson('/api/v1/places?q=Ubud');
    $this->travel(300)->milliseconds();

    $this->getJson('/api/v1/places?q=Kuta');

    Sleep::assertSequence([Sleep::for(700)->milliseconds()]);
});

it('returns 429 with the too_many_requests code when the turn to ask Nominatim does not come within 2 seconds', function () {
    actingAsApiUser();
    Http::fake();
    Cache::lock('nominatim', 10)->acquire();

    $response = $this->getJson('/api/v1/places?q=Ubud');

    $response->assertTooManyRequests()
        ->assertJsonPath('code', 'too_many_requests')
        ->assertHeader('Retry-After', '1');
    Http::assertNothingSent();
});

it('returns 503 with the places_unavailable code when Nominatim fails, and logs it without caching it', function (Closure $failure) {
    actingAsApiUser();
    Log::spy();
    Http::fake(['nominatim.openstreetmap.org/search*' => Http::sequence()
        ->pushResponse($failure())
        ->push([nominatimPlace('Ubud', 'Ubud, Gianyar, Bali, Indonesia', '-8.5068536', '115.2624778')])]);

    $response = $this->getJson('/api/v1/places?q=Ubud');

    $response->assertServiceUnavailable()->assertExactJson([
        'message' => 'Pencarian tempat sedang tidak tersedia. Pin tetap bisa dipasang langsung di peta.',
        'code' => 'places_unavailable',
    ]);
    Log::shouldHaveReceived('warning')->with('Nominatim did not answer a place search.', Mockery::type('array'))->once();
    $this->getJson('/api/v1/places?q=Ubud')->assertOk()->assertJsonPath('data.0.name', 'Ubud');
})->with([
    'an error status' => [fn () => Http::response('Bad Gateway', 502)],
    'no connection' => [fn () => Http::failedConnection()],
]);

it('returns 503 with the places_unavailable code and asks nothing when the search is switched off', function () {
    actingAsApiUser();
    Http::fake();
    config(['services.nominatim.enabled' => false]);

    $response = $this->getJson('/api/v1/places?q=Ubud');

    $response->assertServiceUnavailable()->assertJsonPath('code', 'places_unavailable');
    Http::assertNothingSent();
});

it('returns 422 for words that are missing, too short, or too long', function (string $query) {
    actingAsApiUser();
    Http::fake();

    $response = $this->getJson('/api/v1/places'.$query);

    $response->assertUnprocessable()
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonValidationErrors(['q']);
    Http::assertNothingSent();
})->with([
    'no words' => [''],
    'two characters' => ['?q=ab'],
    'spaces around two characters' => ['?q='.urlencode('  ab  ')],
    'more than 100 characters' => ['?q='.str_repeat('a', 101)],
]);

it('allows 20 searches a minute per account', function () {
    actingAsApiUser();
    Http::fake(['nominatim.openstreetmap.org/search*' => Http::response([])]);
    foreach (range(1, 20) as $search) {
        $this->getJson('/api/v1/places?q=Ubud')->assertOk();
    }

    $response = $this->getJson('/api/v1/places?q=Ubud');

    $response->assertTooManyRequests()->assertJsonPath('code', 'too_many_requests');
});

it('returns 401 when no token is sent', function () {
    $response = $this->getJson('/api/v1/places?q=Ubud');

    $response->assertUnauthorized();
});
