<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $invitation = $this->invitations()->active()->latest()->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'color' => $this->color,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'default_currency' => $this->default_currency,
            'birth_date' => $this->birth_date,
            'notification_prefs' => $this->notification_prefs,
            'avatar_url' => $this->avatarUrl(),
            'avatar_version' => $this->avatar_version,
            'invitation' => $invitation === null ? null : [
                'code' => $invitation->code,
                'expires_at' => $invitation->expires_at,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
