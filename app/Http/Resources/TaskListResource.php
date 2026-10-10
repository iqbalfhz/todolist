<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsPosition;
use App\Models\TaskList;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TaskList
 */
class TaskListResource extends JsonResource
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
            'space_id' => $this->space_id,
            'name' => $this->name,
            'emoji' => $this->emoji,
            'color' => $this->color,
            'position' => $this->position($this->resource->position),
            'archived_at' => $this->archived_at,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
