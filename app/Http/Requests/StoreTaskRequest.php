<?php

namespace App\Http\Requests;

use App\Enums\RepeatFrequency;
use App\Rules\Iso8601WithOffset;
use App\Rules\LowercaseUlid;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'task_list_id' => ['required', new LowercaseUlid],
            'title' => ['required', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'due_date' => ['nullable', 'required_with:due_time', 'date_format:Y-m-d'],
            'due_time' => ['nullable', 'date_format:H:i'],
            'repeat_frequency' => ['nullable', Rule::enum(RepeatFrequency::class)],
            'repeat_interval' => ['nullable', 'integer', 'between:1,99'],
            'priority' => ['nullable', 'integer', 'between:0,3'],
            'position' => ['required', 'numeric'],
            'assignee_ids' => ['sometimes', 'required', 'array', 'max:2'],
            'assignee_ids.*' => ['distinct', new LowercaseUlid],
            'completed_at' => ['nullable', new Iso8601WithOffset],
        ];
    }
}
