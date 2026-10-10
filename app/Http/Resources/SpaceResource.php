<?php

namespace App\Http\Resources;

use App\Enums\SpaceRole;
use App\Models\Space;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Space
 */
class SpaceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $members = $this->members
            ->sortBy(fn (User $member) => [$member->pivot->role !== SpaceRole::Owner->value, $member->pivot->joined_at, $member->id])
            ->values();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'timezone' => $this->timezone,
            'owner_id' => $this->owner_id,
            'members' => SpaceMemberResource::collection($members),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
