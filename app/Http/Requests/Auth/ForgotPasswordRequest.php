<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class ForgotPasswordRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower($this->input('email'))]);
        }
    }
}
