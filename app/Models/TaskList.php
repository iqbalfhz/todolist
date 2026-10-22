<?php

namespace App\Models;

use App\Models\Concerns\HasMicrosecondTimestamps;
use Database\Factories\TaskListFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['space_id', 'trip_id', 'name', 'emoji', 'color', 'position', 'archived_at', 'created_by'])]
class TaskList extends Model
{
    /** @use HasFactory<TaskListFactory> */
    use HasFactory, HasMicrosecondTimestamps, HasUlids, SoftDeletes;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'color' => 'mawar',
    ];

    /**
     * Get the space the list belongs to.
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    /**
     * Get the trip the list is the checklist of, when it is one.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the tasks in the list.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Scope the query to lists that are not archived.
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    /**
     * Scope the query to ordinary lists, leaving out the checklists of trips.
     */
    #[Scope]
    protected function regular(Builder $query): Builder
    {
        return $query->whereNull('trip_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'float',
            'archived_at' => 'datetime',
        ];
    }
}
