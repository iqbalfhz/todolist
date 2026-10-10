<?php

namespace App\Models;

use App\Models\Concerns\HasMicrosecondTimestamps;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'task_list_id',
    'space_id',
    'title',
    'notes',
    'due_date',
    'due_time',
    'priority',
    'position',
    'remind_at',
    'reminder_sent_at',
    'completed_at',
    'completed_by',
    'created_by',
    'updated_by',
])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, HasMicrosecondTimestamps, HasUlids, SoftDeletes;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'priority' => 0,
    ];

    /**
     * Get the list the task belongs to.
     */
    public function taskList(): BelongsTo
    {
        return $this->belongsTo(TaskList::class);
    }

    /**
     * Get the space the task belongs to.
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    /**
     * Get the user who created the task.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the users responsible for the task.
     */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_assignees');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'position' => 'float',
            'remind_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
