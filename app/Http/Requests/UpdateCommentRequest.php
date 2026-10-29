<?php

namespace App\Http\Requests;

use App\Models\Comment;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateCommentRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * The text is the only part of a comment that can change.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['sometimes', 'required', 'string', 'max:'.Comment::MAX_LENGTH],
        ];
    }
}
