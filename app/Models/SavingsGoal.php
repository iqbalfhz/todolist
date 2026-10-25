<?php

namespace App\Models;

use App\Enums\SavingsReminderFrequency;
use App\Models\Concerns\HasMicrosecondTimestamps;
use App\Models\Concerns\NotifiesSpaceChanges;
use Database\Factories\SavingsGoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'space_id',
    'name',
    'emoji',
    'target_amount',
    'currency',
    'target_date',
    'reminder_frequency',
    'reminder_day',
    'achieved_at',
    'achieved_notified_at',
    'archived_at',
    'created_by',
])]
class SavingsGoal extends Model
{
    /** @use HasFactory<SavingsGoalFactory> */
    use HasFactory, HasMicrosecondTimestamps, HasUlids, NotifiesSpaceChanges, SoftDeletes;

    /**
     * Get the space the goal belongs to.
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    /**
     * Get the entries recorded against the goal.
     */
    public function entries(): HasMany
    {
        return $this->hasMany(SavingsEntry::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_amount' => 'integer',
            'reminder_frequency' => SavingsReminderFrequency::class,
            'reminder_day' => 'integer',
            'achieved_at' => 'datetime',
            'achieved_notified_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }
}
