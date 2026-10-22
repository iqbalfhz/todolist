<?php

namespace App\Http\Requests;

use App\Rules\LowercaseUlid;
use Illuminate\Contracts\Validation\ValidationRule;

class StoreTripRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * Whether the end date fits the start date is checked when the trip is saved.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'space_id' => ['required', new LowercaseUlid],
            'title' => ['required', 'string', 'max:50'],
            'destination' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'savings_goal_id' => ['nullable', new LowercaseUlid],
        ];
    }
}
