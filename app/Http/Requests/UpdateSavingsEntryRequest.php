<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class UpdateSavingsEntryRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['sometimes', 'required', 'integer', 'min:1', 'max:'.config('currencies.max_amount')],
            'entry_date' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'note' => ['sometimes', 'nullable', 'string', 'max:200'],
        ];
    }
}
