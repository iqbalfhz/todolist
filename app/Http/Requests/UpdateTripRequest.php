<?php

namespace App\Http\Requests;

use App\Rules\LowercaseUlid;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateTripRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * The space is not listed: a trip stays in the space it was created in.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:50'],
            'destination' => ['sometimes', 'nullable', 'string', 'max:100'],
            'start_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'end_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'savings_goal_id' => ['sometimes', 'nullable', new LowercaseUlid],
        ];
    }
}
