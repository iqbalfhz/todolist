<?php

namespace App\Http\Requests;

use App\Enums\SpecialDateType;
use App\Rules\BirthDate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateSpecialDateRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * The space is not listed: a date stays in the space it was created in.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'required', Rule::enum(SpecialDateType::class)],
            'title' => ['sometimes', 'required', 'string', 'max:50'],
            'date' => ['sometimes', 'required', new BirthDate],
        ];
    }
}
