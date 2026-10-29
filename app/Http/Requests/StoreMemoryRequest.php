<?php

namespace App\Http\Requests;

use App\Rules\LowercaseUlid;
use Illuminate\Contracts\Validation\ValidationRule;

class StoreMemoryRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * Whether the trip is in the same space is checked when the memory is saved.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'space_id' => ['required', new LowercaseUlid],
            'trip_id' => ['nullable', new LowercaseUlid],
            'title' => ['required', 'string', 'max:50'],
            'place_name' => ['nullable', 'string', 'max:100'],
            ...self::locationRules(),
            'visited_on' => ['required', 'date_format:Y-m-d'],
            'story' => ['nullable', 'string', 'max:10000'],
        ];
    }

    /**
     * Get the rules of the pin, whose two halves are always sent together.
     *
     * Both numbers set the pin and both nulls remove it. One without the
     * other is refused, also in a PATCH, so a pin is never half there.
     *
     * @return array<string, array<int, string>>
     */
    public static function locationRules(): array
    {
        return [
            'latitude' => ['present_with:longitude', 'required_with:longitude', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['present_with:latitude', 'required_with:latitude', 'nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
