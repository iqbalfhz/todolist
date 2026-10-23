<?php

namespace App\Http\Resources;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{changes: array<string, Collection<int, Model>>, server_time: CarbonImmutable, next_cursor: string|null} $resource
 */
class SyncResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $changes = $this->resource['changes'];

        return [
            'spaces' => SpaceResource::collection($changes['spaces']),
            'task_lists' => TaskListResource::collection($changes['task_lists']),
            'tasks' => TaskResource::collection($changes['tasks']),
            'savings_goals' => SavingsGoalResource::collection($changes['savings_goals']),
            'savings_entries' => SavingsEntryResource::collection($changes['savings_entries']),
            'special_dates' => SpecialDateResource::collection($changes['special_dates']),
            'trips' => TripResource::collection($changes['trips']),
            'memories' => MemoryResource::collection($changes['memories']),
            'memory_photos' => MemoryPhotoResource::collection($changes['memory_photos']),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'server_time' => $this->resource['server_time'],
                'has_more' => $this->resource['next_cursor'] !== null,
                /** @var string|null */
                'next_cursor' => $this->resource['next_cursor'],
            ],
        ];
    }
}
