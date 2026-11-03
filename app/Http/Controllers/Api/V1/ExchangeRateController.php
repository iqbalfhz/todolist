<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\FetchExchangeRates;
use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExchangeRateResource;
use App\Models\ExchangeRate;

class ExchangeRateController extends Controller
{
    /**
     * The latest reference rates the server stored, for the savings estimates. It never waits for the ECB.
     */
    public function show(): ExchangeRateResource
    {
        $exchangeRate = ExchangeRate::query()->firstWhere('base', FetchExchangeRates::BASE)
            ?? throw new ApiException(ErrorCode::ExchangeRatesUnavailable);

        return new ExchangeRateResource($exchangeRate);
    }
}
