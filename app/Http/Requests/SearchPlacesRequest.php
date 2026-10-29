<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class SearchPlacesRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:3', 'max:100'],
        ];
    }
}
