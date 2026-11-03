<?php

namespace App\Http\Requests;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreEmailChangeRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * The new email follows the rules of registration. The admin account
     * keeps its email, since the admin panel knows the admin by it. The
     * change is confirmed with the password, or with an ID token from the
     * linked Google account, after validation.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'bail',
                'required',
                'string',
                'email',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->user()->isAdmin()) {
                        $fail(__('api.account.admin_email'));
                    } elseif (Str::lower($value) === Str::lower($this->user()->email)) {
                        $fail(__('api.account.same_email'));
                    }
                },
                Rule::unique(User::class),
            ],
            'password' => ['nullable', 'required_without:id_token', 'string', 'max:255'],
            'id_token' => ['nullable', 'required_without:password', 'string', 'max:4096'],
        ];
    }

    /**
     * Get the custom messages for the validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => __('api.account.email_taken'),
        ];
    }
}
