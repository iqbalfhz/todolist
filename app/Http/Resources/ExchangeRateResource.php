<?php

namespace App\Http\Resources;

use App\Models\ExchangeRate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ExchangeRate
 */
class ExchangeRateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'base' => $this->base,
            'date' => $this->rate_date,
            'fetched_at' => $this->fetched_at,
            'rates' => $this->rates,
        ];
    }
}
