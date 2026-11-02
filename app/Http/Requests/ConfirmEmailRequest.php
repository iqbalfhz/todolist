<?php

namespace App\Http\Requests;

use App\Models\VerificationCode;
use Illuminate\Contracts\Validation\ValidationRule;

class ConfirmEmailRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'digits:'.VerificationCode::LENGTH],
        ];
    }
}
