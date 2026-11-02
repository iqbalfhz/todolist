<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiRequest;
use App\Models\VerificationCode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends ApiRequest
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
            'code' => ['required', 'string', 'digits:'.VerificationCode::LENGTH],
            'password' => ['required', 'string', Password::defaults()],
            'device_name' => ['required', 'string', 'max:255'],
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
