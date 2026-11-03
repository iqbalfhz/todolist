<?php

namespace App\Models;

use App\Models\Concerns\HasMicrosecondTimestamps;
use Database\Factories\ExchangeRateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The latest daily reference rates of the ECB, for the savings estimates in the app.
 *
 * `rates` holds how many units of each currency make one unit of `base`, as
 * the decimal text the ECB publishes.
 */
#[Fillable(['base', 'rate_date', 'rates', 'fetched_at'])]
class ExchangeRate extends Model
{
    /** @use HasFactory<ExchangeRateFactory> */
    use HasFactory, HasMicrosecondTimestamps, HasUlids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rates' => 'array',
            'fetched_at' => 'datetime',
        ];
    }
}
