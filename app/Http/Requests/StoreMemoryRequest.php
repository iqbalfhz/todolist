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
            'visited_on' => ['required', 'date_format:Y-m-d'],
            'story' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
