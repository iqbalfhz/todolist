<?php

namespace App\Http\Requests;

use App\Enums\SavingsReminderFrequency;
use App\Rules\LowercaseUlid;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class StoreSavingsGoalRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'space_id' => ['required', new LowercaseUlid],
            'name' => ['required', 'string', 'max:50'],
            'emoji' => ['nullable', 'string', 'max:16'],
            'target_amount' => ['required', 'integer', 'min:1', 'max:'.config('currencies.max_amount')],
            'currency' => ['required', Rule::in(array_keys(config('currencies.decimals')))],
            'target_date' => ['nullable', 'date_format:Y-m-d'],
            'reminder_frequency' => ['nullable', Rule::enum(SavingsReminderFrequency::class)],
            'reminder_day' => ['nullable', 'integer', 'min:1', 'max:28'],
        ];
    }
}
