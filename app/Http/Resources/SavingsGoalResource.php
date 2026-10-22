<?php

namespace App\Http\Resources;

use App\Models\SavingsGoal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SavingsGoal
 */
class SavingsGoalResource extends JsonResource
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
            'space_id' => $this->space_id,
            'name' => $this->name,
            'emoji' => $this->emoji,
            'target_amount' => $this->target_amount,
            'currency' => $this->currency,
            'target_date' => $this->target_date,
            'reminder_frequency' => $this->reminder_frequency,
            'reminder_day' => $this->reminder_day,
            'achieved_at' => $this->achieved_at,
            'archived_at' => $this->archived_at,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
