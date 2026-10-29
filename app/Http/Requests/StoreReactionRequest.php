<?php

namespace App\Http\Requests;

use App\Enums\SubjectType;
use App\Rules\LowercaseUlid;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class StoreReactionRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * The app makes sure the emoji is a single one; the server only bounds
     * its length, as it does for the emoji of a list. Whether the subject
     * exists, is reachable, and sits in a shared space is checked when the
     * reaction is stored.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject_type' => ['required', Rule::enum(SubjectType::class)],
            'subject_id' => ['required', new LowercaseUlid],
            'emoji' => ['required', 'string', 'max:16'],
        ];
    }
}
