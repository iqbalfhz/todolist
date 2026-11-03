<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * The change is confirmed with the current password, or with an ID token
     * from the linked Google account when the account has no password. Both
     * are checked after validation; when both are sent, the password counts.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['nullable', 'required_without:id_token', 'string', 'max:255'],
            'id_token' => ['nullable', 'required_without:current_password', 'string', 'max:4096'],
            'password' => ['required', 'string', Password::defaults()],
            'fcm_token' => ['nullable', 'string', 'max:512'],
        ];
    }
}
