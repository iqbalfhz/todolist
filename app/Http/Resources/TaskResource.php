<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsPosition;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Task
 */
class TaskResource extends JsonResource
{
    use FormatsPosition;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_list_id' => $this->task_list_id,
            'space_id' => $this->space_id,
            'title' => $this->title,
            'notes' => $this->notes,
            'due_date' => $this->due_date,
            'due_time' => $this->due_time === null ? null : substr($this->due_time, 0, 5),
            'repeat_frequency' => $this->repeat_frequency,
            'repeat_interval' => $this->repeat_interval,
            'priority' => $this->priority,
            'position' => $this->position($this->resource->position),
            'assignee_ids' => $this->assignees->pluck('id')->sort()->values(),
            'remind_at' => $this->remind_at,
            'completed_at' => $this->completed_at,
            'completed_by' => $this->completed_by,
            'next_task_id' => $this->next_task_id,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
