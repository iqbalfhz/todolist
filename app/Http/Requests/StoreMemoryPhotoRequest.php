<?php

namespace App\Http\Requests;

use App\Rules\Iso8601WithOffset;
use App\Rules\LowercaseUlid;
use App\Rules\Position;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class StoreMemoryPhotoRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * The app sends a JPEG of at most 2560 px; the limits leave room for a
     * photo it could not shrink. Whether the memory still has room is
     * checked when the photo is saved.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'memory_id' => ['required', new LowercaseUlid],
            'photo' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:10240',
                Rule::dimensions()->maxWidth(4096)->maxHeight(4096),
            ],
            'position' => ['required', 'numeric', new Position],
            'taken_at' => ['nullable', new Iso8601WithOffset],
        ];
    }
}
