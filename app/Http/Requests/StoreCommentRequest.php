<?php

namespace App\Http\Requests;

use App\Enums\SubjectType;
use App\Models\Comment;
use App\Rules\LowercaseUlid;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class StoreCommentRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * Whether the subject exists, is reachable, and sits in a shared space is
     * checked when the comment is stored.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject_type' => ['required', Rule::enum(SubjectType::class)],
            'subject_id' => ['required', new LowercaseUlid],
            'body' => ['required', 'string', 'max:'.Comment::MAX_LENGTH],
        ];
    }
}
