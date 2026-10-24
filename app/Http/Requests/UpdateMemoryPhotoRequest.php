<?php

namespace App\Http\Requests;

use App\Rules\Position;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateMemoryPhotoRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * Only the order can change; the file and the memory stay as uploaded.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'position' => ['sometimes', 'required', 'numeric', new Position],
        ];
    }
}
