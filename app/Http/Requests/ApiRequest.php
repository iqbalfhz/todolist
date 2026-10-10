<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class ApiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Access to a space is checked by policies, which answer 404 instead of 403.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the Indonesian field names used in validation messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return __('api.attributes');
    }
}
