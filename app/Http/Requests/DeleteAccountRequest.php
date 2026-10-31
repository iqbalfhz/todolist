<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class DeleteAccountRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * Deleting the account is confirmed with its password, or with an ID
     * token from the Google account it is linked to. Both are checked after
     * validation; when both are sent, the password counts.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => ['nullable', 'required_without:id_token', 'string', 'max:255'],
            'id_token' => ['nullable', 'required_without:password', 'string', 'max:4096'],
        ];
    }
}
