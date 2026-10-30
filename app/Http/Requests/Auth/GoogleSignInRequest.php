<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class GoogleSignInRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * Whether the ID token is genuine is checked after validation, against
     * Google's public keys. The timezone only applies to a new account.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id_token' => ['required', 'string', 'max:4096'],
            'device_name' => ['required', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'timezone:all'],
        ];
    }
}
