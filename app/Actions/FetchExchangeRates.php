<?php

namespace App\Actions;

use App\Models\ExchangeRate;
use DateTimeImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

/**
 * Fetches the daily euro reference rates of the European Central Bank.
 *
 * The ECB publishes them once a working day, around 16.00 in Frankfurt. Its
 * statistics may be reused for free as long as the figures are not changed
 * and the source is named, so the rates are stored exactly as published:
 * https://www.ecb.europa.eu/stats/ecb_statistics/governance_and_quality_framework/html/usage_policy.en.html
 */
class FetchExchangeRates
{
    /**
     * The file that always holds the latest reference rates.
     */
    public const SOURCE_URL = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';

    /**
     * The currency every rate of the ECB is quoted against.
     */
    public const BASE = 'EUR';

    /**
     * Fetch the latest rates and store them in place of the previous ones.
     *
     * A fetch that fails, is incomplete, or brings an older day than the one
     * stored leaves the stored rates alone and only writes a warning to the log.
     */
    public function handle(): ?ExchangeRate
    {
        $body = $this->download();
        $published = $body === null ? null : $this->read($body);

        if ($published === null) {
            return null;
        }

        $stored = ExchangeRate::query()->firstWhere('base', self::BASE);

        if ($stored !== null && $published['date'] < $stored->rate_date) {
            Log::warning('The ECB sent exchange rates older than the stored ones.', [
                'sent' => $published['date'],
                'stored' => $stored->rate_date,
            ]);

            return null;
        }

        return ExchangeRate::query()->updateOrCreate(['base' => self::BASE], [
            'rate_date' => $published['date'],
            'rates' => $published['rates'],
            'fetched_at' => now(),
        ]);
    }

    /**
     * Download the file of the latest rates. Nothing about any account goes out.
     */
    private function download(): ?string
    {
        try {
            return Http::withUserAgent('DenlyDays/1.0 (+'.config('app.url').')')
                ->timeout(10)
                ->get(self::SOURCE_URL)
                ->throw()
                ->body();
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('The ECB did not answer the exchange rate request.', [
                'status' => $exception instanceof RequestException ? $exception->response->status() : null,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Read the day and the rates of the currencies the app supports, the euro being 1.
     *
     * @return array{date: string, rates: array<string, string>}|null
     */
    private function read(string $body): ?array
    {
        $day = $this->day($body);
        $date = $day === null ? '' : (string) $day['time'];

        if (! $this->isDate($date)) {
            Log::warning('The exchange rate file of the ECB has no valid day.');

            return null;
        }

        $published = [];

        foreach ($day->Cube as $rate) {
            $published[(string) $rate['currency']] = (string) $rate['rate'];
        }

        $rates = [];

        foreach (array_keys(config('currencies.decimals')) as $currency) {
            $rate = $currency === self::BASE ? '1' : ($published[$currency] ?? '');

            if (! $this->isRate($rate)) {
                Log::warning('The exchange rate file of the ECB has no valid rate for a supported currency.', [
                    'currency' => $currency,
                ]);

                return null;
            }

            $rates[$currency] = $rate;
        }

        return ['date' => $date, 'rates' => $rates];
    }

    /**
     * Find the element of the day in the file, without letting the XML reach the network.
     */
    private function day(string $body): ?SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);

        try {
            $file = simplexml_load_string($body, options: LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return $file === false ? null : ($file->Cube->Cube ?? null);
    }

    /**
     * Whether the text is a real day written as YYYY-MM-DD.
     */
    private function isDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    /**
     * Whether the text is a positive decimal number, as the ECB writes its rates.
     */
    private function isRate(string $rate): bool
    {
        return preg_match('/^\d{1,9}(\.\d{1,9})?$/', $rate) === 1 && (float) $rate > 0;
    }
}
