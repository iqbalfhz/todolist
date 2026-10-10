<?php

namespace App\Http\Resources;

use App\Models\SavingsEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SavingsEntry
 */
class SavingsEntryResource extends JsonResource
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
            'savings_goal_id' => $this->savings_goal_id,
            'space_id' => $this->space_id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'amount' => $this->amount,
            'entry_date' => $this->entry_date,
            'note' => $this->note,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
