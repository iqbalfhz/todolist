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

#[Fillable(['space_id', 'name', 'emoji', 'color', 'position', 'archived_at', 'created_by'])]
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
