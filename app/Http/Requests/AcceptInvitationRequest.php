<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class AcceptInvitationRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32'],
        ];
    }
}
