<?php

use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Http;

it('returns the stored rates in the shape of the contract, without asking the ECB', function () {
    actingAsApiUser();
    Http::fake();
    ExchangeRate::factory()->create(['fetched_at' => '2026-11-03 07:00:03.512345']);

    $response = $this->getJson('/api/v1/exchange-rates');

    $response->assertOk()->assertExactJson(['data' => [
        'base' => 'EUR',
        'date' => '2026-11-02',
        'fetched_at' => '2026-11-03T07:00:03.512345Z',
        'rates' => [
            'EUR' => '1',
            'IDR' => '20341.45',
            'JPY' => '177.10',
            'MYR' => '4.5655',
            'SGD' => '1.4344',
            'USD' => '1.1216',
        ],
    ]]);
    Http::assertNothingSent();
});

it('returns 503 with the exchange_rates_unavailable code before the server has ever fetched the rates', function () {
    actingAsApiUser();
    Http::fake();

    $response = $this->getJson('/api/v1/exchange-rates');

    $response->assertServiceUnavailable()->assertExactJson([
        'message' => 'Kurs belum tersedia. Coba lagi nanti.',
        'code' => 'exchange_rates_unavailable',
    ]);
    Http::assertNothingSent();
});

it('returns 401 without a token', function () {
    ExchangeRate::factory()->create();

    $this->getJson('/api/v1/exchange-rates')
        ->assertUnauthorized()
        ->assertJsonPath('code', 'unauthenticated');
});
