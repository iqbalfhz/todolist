<?php

namespace App\Models;

use App\Models\Concerns\HasMicrosecondTimestamps;
use Database\Factories\TripFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'space_id',
    'title',
    'destination',
    'start_date',
    'end_date',
    'notes',
    'savings_goal_id',
    'created_by',
])]
class Trip extends Model
{
    /** @use HasFactory<TripFactory> */
    use HasFactory, HasMicrosecondTimestamps, HasUlids, SoftDeletes;

    /**
     * Get the space the trip belongs to.
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    /**
     * Get the savings goal the trip is saved up for.
     */
    public function savingsGoal(): BelongsTo
    {
        return $this->belongsTo(SavingsGoal::class);
    }

    /**
     * Get the list that holds the trip's packing checklist.
     */
    public function checklist(): HasOne
    {
        return $this->hasOne(TaskList::class);
    }

    /**
     * Get the tasks of the trip's packing checklist.
     */
    public function checklistTasks(): HasManyThrough
    {
        return $this->hasManyThrough(Task::class, TaskList::class);
    }
}
