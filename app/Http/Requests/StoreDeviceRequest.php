<?php

namespace App\Http\Requests;

use App\Enums\DevicePlatform;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class StoreDeviceRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'ascii', 'max:512'],
            'platform' => ['required', Rule::enum(DevicePlatform::class)],
        ];
    }
}
