<?php

namespace App\Models;

use App\Enums\SpaceType;
use App\Models\Concerns\HasMicrosecondTimestamps;
use App\Models\Concerns\NotifiesSpaceChanges;
use Database\Factories\SpaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'type', 'timezone', 'owner_id'])]
class Space extends Model
{
    /** @use HasFactory<SpaceFactory> */
    use HasFactory, HasMicrosecondTimestamps, HasUlids, NotifiesSpaceChanges, SoftDeletes;

    /**
     * How many days a closed shared space is kept, so the same two partners
     * can get it back by pairing again. After that it is deleted for good.
     */
    public const RESTORABLE_DAYS = 30;

    /**
     * The models that hold what a space contains, in an order in which they
     * can be deleted for good without breaking a foreign key: a model comes
     * before every model it points to.
     *
     * Every collection GET /sync sends besides the spaces themselves is here.
     *
     * @var list<class-string<Model>>
     */
    public const CONTENT_MODELS = [
        Comment::class,
        Reaction::class,
        MemoryPhoto::class,
        Memory::class,
        SavingsEntry::class,
        Task::class,
        TaskList::class,
        Trip::class,
        SavingsGoal::class,
        SpecialDate::class,
    ];

    /**
     * Get the ID of the space the row belongs to, which for a space is its own.
     */
    public function changedSpaceId(): string
    {
        return $this->id;
    }

    /**
     * Get the user who owns the space.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the users who belong to the space.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'space_members')->withPivot(['role', 'joined_at']);
    }

    /**
     * Get the task lists in the space.
     */
    public function taskLists(): HasMany
    {
        return $this->hasMany(TaskList::class);
    }

    /**
     * Get the tasks in the space.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the savings goals in the space.
     */
    public function savingsGoals(): HasMany
    {
        return $this->hasMany(SavingsGoal::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => SpaceType::class,
        ];
    }
}
