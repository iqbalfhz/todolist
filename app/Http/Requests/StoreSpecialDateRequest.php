<?php

namespace App\Http\Requests;

use App\Enums\SpecialDateType;
use App\Rules\BirthDate;
use App\Rules\LowercaseUlid;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class StoreSpecialDateRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * The date takes the same format as a birth date: the year may be left out.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'space_id' => ['required', new LowercaseUlid],
            'type' => ['required', Rule::enum(SpecialDateType::class)],
            'title' => ['required', 'string', 'max:50'],
            'date' => ['required', new BirthDate],
        ];
    }
}
