<?php

namespace App\Actions;

use App\Models\Comment;
use App\Models\Memory;
use App\Models\MemoryPhoto;
use App\Models\Reaction;
use App\Models\SavingsEntry;
use App\Models\SavingsGoal;
use App\Models\Space;
use App\Models\SpecialDate;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class QuerySyncCollection
{
    /**
     * Start the query for one of the collections GET /sync sends, limited to
     * the given spaces, with what its resource reads already loaded.
     *
     * The data export reads the same collections the same way, so both
     * always send the same rows in the same shape.
     *
     * @param  Collection<int, string>  $spaceIds
     * @return Builder<Model>
     */
    public function handle(string $collection, Collection $spaceIds): Builder
    {
        return match ($collection) {
            'spaces' => Space::query()->whereKey($spaceIds)->with('members'),
            'task_lists' => TaskList::query()->whereIn('space_id', $spaceIds),
            'tasks' => Task::query()->whereIn('space_id', $spaceIds)->with('assignees:id'),
            'savings_goals' => SavingsGoal::query()->whereIn('space_id', $spaceIds),
            'savings_entries' => SavingsEntry::query()->whereIn('space_id', $spaceIds),
            'special_dates' => SpecialDate::query()->whereIn('space_id', $spaceIds),
            'trips' => Trip::query()->whereIn('space_id', $spaceIds),
            'memories' => Memory::query()->whereIn('space_id', $spaceIds),
            'memory_photos' => MemoryPhoto::query()->whereIn('space_id', $spaceIds),
            'comments' => Comment::query()->whereIn('space_id', $spaceIds),
            'reactions' => Reaction::query()->whereIn('space_id', $spaceIds),
        };
    }
}
