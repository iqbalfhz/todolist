<?php

namespace App\Http\Requests;

use App\Enums\ProfileColor;
use App\Rules\LowercaseUlid;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class StoreTaskListRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'space_id' => ['required', new LowercaseUlid],
            'trip_id' => ['nullable', new LowercaseUlid],
            'name' => ['required', 'string', 'max:50'],
            'emoji' => ['nullable', 'string', 'max:16'],
            'color' => ['nullable', Rule::enum(ProfileColor::class)],
            'position' => ['required', 'numeric'],
        ];
    }
}
