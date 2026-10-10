<?php

namespace App\Http\Requests;

use App\Enums\ProfileColor;
use App\Rules\Iso8601WithOffset;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateTaskListRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:50'],
            'emoji' => ['sometimes', 'nullable', 'string', 'max:16'],
            'color' => ['sometimes', 'required', Rule::enum(ProfileColor::class)],
            'position' => ['sometimes', 'required', 'numeric'],
            'archived_at' => ['sometimes', 'nullable', new Iso8601WithOffset],
        ];
    }
}
