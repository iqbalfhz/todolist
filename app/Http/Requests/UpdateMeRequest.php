<?php

namespace App\Http\Requests;

use App\Enums\ProfileColor;
use App\Rules\BirthDate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateMeRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:50'],
            'color' => ['sometimes', 'required', Rule::enum(ProfileColor::class)],
            'timezone' => ['sometimes', 'required', 'string', 'timezone:all'],
            'default_currency' => ['sometimes', 'required', Rule::in(array_keys(config('currencies.decimals')))],
            'birth_date' => ['sometimes', 'nullable', new BirthDate],
            'notification_prefs' => ['sometimes', 'required', 'array'],
            'notification_prefs.task_reminders' => ['sometimes', 'required', 'boolean'],
            'notification_prefs.partner_activity' => ['sometimes', 'required', 'boolean'],
            'notification_prefs.savings_activity' => ['sometimes', 'required', 'boolean'],
            'notification_prefs.special_dates' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
