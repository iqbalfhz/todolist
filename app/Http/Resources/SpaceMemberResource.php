<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * A user as the other members of a space see them. Expects the membership pivot to be loaded.
 *
 * @mixin User
 */
class SpaceMemberResource extends JsonResource
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
            'name' => $this->name,
            'color' => $this->color,
            'avatar_url' => $this->avatarUrl(),
            'avatar_version' => $this->avatar_version,
            'birth_date' => $this->birth_date,
            'role' => $this->pivot->role,
            'joined_at' => Carbon::parse($this->pivot->joined_at),
        ];
    }
}
