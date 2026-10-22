<?php

namespace App\Http\Requests;

use App\Enums\SavingsReminderFrequency;
use App\Rules\Iso8601WithOffset;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateSavingsGoalRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * The currency is not listed: it can only be chosen when the goal is created.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:50'],
            'emoji' => ['sometimes', 'nullable', 'string', 'max:16'],
            'target_amount' => ['sometimes', 'required', 'integer', 'min:1', 'max:'.config('currencies.max_amount')],
            'target_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'reminder_frequency' => ['sometimes', 'nullable', Rule::enum(SavingsReminderFrequency::class)],
            'reminder_day' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:31'],
            'archived_at' => ['sometimes', 'nullable', new Iso8601WithOffset],
        ];
    }
}
