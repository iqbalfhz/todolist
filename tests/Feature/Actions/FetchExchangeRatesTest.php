<?php

use App\Actions\FetchExchangeRates;
use App\Models\ExchangeRate;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

it('stores the rates of the six supported currencies exactly as the ECB publishes them, with the euro as 1', function () {
    $this->travelTo('2026-11-03 15:05:12.345678');
    Http::fake([FetchExchangeRates::SOURCE_URL => Http::response(ecbDailyRates(), 200, ['Content-Type' => 'text/xml'])]);

    $exchangeRate = app(FetchExchangeRates::class)->handle();

    $stored = ExchangeRate::query()->sole();
    expect($exchangeRate->is($stored))->toBeTrue()
        ->and($stored->base)->toBe('EUR')
        ->and($stored->rate_date)->toBe('2026-11-02')
        ->and($stored->rates)->toBe([
            'EUR' => '1',
            'IDR' => '20341.45',
            'JPY' => '177.10',
            'MYR' => '4.5655',
            'SGD' => '1.4344',
            'USD' => '1.1216',
        ])
        ->and($stored->fetched_at->format('Y-m-d H:i:s.u'))->toBe('2026-11-03 15:05:12.345678');
});

it('asks the ECB for its daily file as Denly Days, with nothing about any account', function () {
    Http::fake([FetchExchangeRates::SOURCE_URL => Http::response(ecbDailyRates())]);

    app(FetchExchangeRates::class)->handle();

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && $request->url() === 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml'
        && $request->header('User-Agent') === ['DenlyDays/1.0 (+'.config('app.url').')']);
});

it('replaces the stored rates when the ECB publishes a newer day', function () {
    $this->travelTo('2026-10-30 15:05:00');
    ExchangeRate::factory()->create(['rate_date' => '2026-10-30']);
    $this->travelTo('2026-11-03 15:05:00');
    Http::fake([FetchExchangeRates::SOURCE_URL => Http::response(ecbDailyRates('2026-11-03', [
        'USD' => '1.1302',
        'JPY' => '176.85',
        'IDR' => '20410.10',
        'MYR' => '4.5720',
        'SGD' => '1.4398',
    ]))]);

    app(FetchExchangeRates::class)->handle();

    $stored = ExchangeRate::query()->sole();
    expect($stored->rate_date)->toBe('2026-11-03')
        ->and($stored->rates)->toBe([
            'EUR' => '1',
            'IDR' => '20410.10',
            'JPY' => '176.85',
            'MYR' => '4.5720',
            'SGD' => '1.4398',
            'USD' => '1.1302',
        ])
        ->and($stored->fetched_at->toDateTimeString())->toBe('2026-11-03 15:05:00');
});

it('keeps the day and only moves the fetch time while the ECB still has the same day', function () {
    $this->travelTo('2026-11-02 15:05:00');
    ExchangeRate::factory()->create(['rate_date' => '2026-11-02']);
    $this->travelTo('2026-11-02 16:00:00');
    Http::fake([FetchExchangeRates::SOURCE_URL => Http::response(ecbDailyRates('2026-11-02'))]);

    app(FetchExchangeRates::class)->handle();

    $stored = ExchangeRate::query()->sole();
    expect($stored->rate_date)->toBe('2026-11-02')
        ->and($stored->fetched_at->toDateTimeString())->toBe('2026-11-02 16:00:00');
});

it('keeps the stored rates and warns in the log when the ECB sends an older day', function () {
    $this->travelTo('2026-11-03 15:05:00');
    $stored = ExchangeRate::factory()->create(['rate_date' => '2026-11-03', 'rates' => ['USD' => '1.1302']]);
    $this->travelTo('2026-11-03 16:05:00');
    Http::fake([FetchExchangeRates::SOURCE_URL => Http::response(ecbDailyRates('2026-11-02'))]);
    Log::spy();

    expect(app(FetchExchangeRates::class)->handle())->toBeNull();

    $kept = $stored->fresh();
    expect($kept->rate_date)->toBe('2026-11-03')
        ->and($kept->rates)->toBe(['USD' => '1.1302'])
        ->and($kept->fetched_at->toDateTimeString())->toBe('2026-11-03 15:05:00');
    Log::shouldHaveReceived('warning')
        ->with('The ECB sent exchange rates older than the stored ones.', ['sent' => '2026-11-02', 'stored' => '2026-11-03'])
        ->once();
});

it('keeps the stored rates and warns in the log when the ECB does not answer', function (Closure $failure) {
    $this->travelTo('2026-10-30 15:05:00');
    $stored = ExchangeRate::factory()->create(['rate_date' => '2026-10-30', 'rates' => ['USD' => '1.1302']]);
    $this->travelTo('2026-11-03 15:05:00');
    Http::fake([FetchExchangeRates::SOURCE_URL => $failure()]);
    Log::spy();

    expect(app(FetchExchangeRates::class)->handle())->toBeNull();

    $kept = $stored->fresh();
    expect($kept->rate_date)->toBe('2026-10-30')
        ->and($kept->rates)->toBe(['USD' => '1.1302'])
        ->and($kept->fetched_at->toDateTimeString())->toBe('2026-10-30 15:05:00');
    Log::shouldHaveReceived('warning')
        ->with('The ECB did not answer the exchange rate request.', Mockery::type('array'))
        ->once();
})->with([
    'an error status, even with a usable file' => [fn () => Http::response(ecbDailyRates('2026-11-03'), 500)],
    'no connection' => [fn () => Http::failedConnection()],
]);

it('keeps the stored rates and warns in the log when the file has no usable day', function (Closure $body) {
    $this->travelTo('2026-10-30 15:05:00');
    $stored = ExchangeRate::factory()->create(['rate_date' => '2026-10-30']);
    $this->travelTo('2026-11-03 15:05:00');
    Http::fake([FetchExchangeRates::SOURCE_URL => Http::response($body())]);
    Log::spy();

    expect(app(FetchExchangeRates::class)->handle())->toBeNull();

    expect($stored->fresh()->rate_date)->toBe('2026-10-30');
    Log::shouldHaveReceived('warning')->with('The exchange rate file of the ECB has no valid day.')->once();
})->with([
    'an empty answer' => [fn () => ''],
    'a page that is not XML' => [fn () => '<html><body>Bad Gateway'],
    'XML of something else' => [fn () => '<?xml version="1.0"?><error>Service unavailable</error>'],
    'no day on the rates' => [fn () => str_replace(" time='2026-11-03'", '', ecbDailyRates('2026-11-03'))],
    'a day that does not exist' => [fn () => ecbDailyRates('2026-02-30')],
    'a day written another way' => [fn () => ecbDailyRates('03/11/2026')],
]);

it('keeps the stored rates and warns in the log when a supported currency has no usable rate', function (array $rates) {
    $this->travelTo('2026-10-30 15:05:00');
    $stored = ExchangeRate::factory()->create(['rate_date' => '2026-10-30']);
    $this->travelTo('2026-11-03 15:05:00');
    Http::fake([FetchExchangeRates::SOURCE_URL => Http::response(ecbDailyRates('2026-11-03', $rates))]);
    Log::spy();

    expect(app(FetchExchangeRates::class)->handle())->toBeNull();

    expect($stored->fresh()->rate_date)->toBe('2026-10-30');
    Log::shouldHaveReceived('warning')
        ->with('The exchange rate file of the ECB has no valid rate for a supported currency.', ['currency' => 'IDR'])
        ->once();
})->with([
    'a day without any rates' => [[]],
    'missing' => [['USD' => '1.1216', 'JPY' => '177.10', 'MYR' => '4.5655', 'SGD' => '1.4344']],
    'not a number' => [['USD' => '1.1216', 'JPY' => '177.10', 'IDR' => 'n/a', 'MYR' => '4.5655', 'SGD' => '1.4344']],
    'zero' => [['USD' => '1.1216', 'JPY' => '177.10', 'IDR' => '0.00', 'MYR' => '4.5655', 'SGD' => '1.4344']],
    'negative' => [['USD' => '1.1216', 'JPY' => '177.10', 'IDR' => '-20341.45', 'MYR' => '4.5655', 'SGD' => '1.4344']],
    'with a decimal comma' => [['USD' => '1.1216', 'JPY' => '177.10', 'IDR' => '20341,45', 'MYR' => '4.5655', 'SGD' => '1.4344']],
    'empty' => [['USD' => '1.1216', 'JPY' => '177.10', 'IDR' => '', 'MYR' => '4.5655', 'SGD' => '1.4344']],
]);

it('stores nothing and warns in the log when the first fetch fails', function () {
    Http::fake([FetchExchangeRates::SOURCE_URL => Http::response('Bad Gateway', 502)]);
    Log::spy();

    expect(app(FetchExchangeRates::class)->handle())->toBeNull();

    $this->assertDatabaseCount('exchange_rates', 0);
    Log::shouldHaveReceived('warning')->once();
});
