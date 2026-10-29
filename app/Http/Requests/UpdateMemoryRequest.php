<?php

namespace App\Http\Requests;

use App\Rules\LowercaseUlid;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateMemoryRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * The space is not listed: a memory stays in the space it was created in.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'trip_id' => ['sometimes', 'nullable', new LowercaseUlid],
            'title' => ['sometimes', 'required', 'string', 'max:50'],
            'place_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            ...StoreMemoryRequest::locationRules(),
            'visited_on' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'story' => ['sometimes', 'nullable', 'string', 'max:10000'],
        ];
    }
}
