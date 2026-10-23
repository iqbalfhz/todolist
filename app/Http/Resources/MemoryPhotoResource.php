<?php

namespace App\Http\Resources;

use App\Models\MemoryPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MemoryPhoto
 */
class MemoryPhotoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'memory_id' => $this->memory_id,
            'space_id' => $this->space_id,
            'uploaded_by' => $this->uploaded_by,
            'position' => $this->position,
            'width' => $this->width,
            'height' => $this->height,
            'taken_at' => $this->taken_at,
            'url' => $this->url(),
            'thumb_url' => $this->thumbUrl(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
