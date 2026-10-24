<?php

namespace App\Http\Requests;

use App\Enums\RepeatFrequency;
use App\Rules\Iso8601WithOffset;
use App\Rules\LowercaseUlid;
use App\Rules\Position;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'task_list_id' => ['sometimes', 'required', new LowercaseUlid],
            'title' => ['sometimes', 'required', 'string', 'max:200'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'due_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'due_time' => ['sometimes', 'nullable', 'date_format:H:i'],
            'repeat_frequency' => ['sometimes', 'nullable', Rule::enum(RepeatFrequency::class)],
            'repeat_interval' => ['sometimes', 'nullable', 'integer', 'between:1,99'],
            'priority' => ['sometimes', 'required', 'integer', 'between:0,3'],
            'position' => ['sometimes', 'required', 'numeric', new Position],
            'assignee_ids' => ['sometimes', 'required', 'array', 'max:2'],
            'assignee_ids.*' => ['distinct', new LowercaseUlid],
            'completed_at' => ['sometimes', 'nullable', new Iso8601WithOffset],
        ];
    }
}
