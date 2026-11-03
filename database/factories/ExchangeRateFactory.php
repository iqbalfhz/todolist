<?php

namespace Database\Factories;

use App\Models\ExchangeRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExchangeRate>
 */
class ExchangeRateFactory extends Factory
{
    /**
     * Define the model's default state: the ECB rates of 2 November 2026, fetched just now.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'base' => 'EUR',
            'rate_date' => '2026-11-02',
            'rates' => [
                'EUR' => '1',
                'IDR' => '20341.45',
                'JPY' => '177.10',
                'MYR' => '4.5655',
                'SGD' => '1.4344',
                'USD' => '1.1216',
            ],
            'fetched_at' => now(),
        ];
    }
}
