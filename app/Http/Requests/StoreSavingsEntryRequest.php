<?php

namespace App\Http\Requests;

use App\Enums\SavingsEntryType;
use App\Rules\LowercaseUlid;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class StoreSavingsEntryRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'savings_goal_id' => ['required', new LowercaseUlid],
            'type' => ['sometimes', Rule::enum(SavingsEntryType::class)],
            'amount' => ['required', 'integer', 'min:1', 'max:'.config('currencies.max_amount')],
            'entry_date' => ['required', 'date_format:Y-m-d'],
            'note' => ['nullable', 'string', 'max:200'],
        ];
    }
}
