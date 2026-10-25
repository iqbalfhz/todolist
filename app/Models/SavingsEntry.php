<?php

namespace App\Models;

use App\Models\Concerns\HasMicrosecondTimestamps;
use App\Models\Concerns\NotifiesSpaceChanges;
use Database\Factories\SavingsEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['savings_goal_id', 'space_id', 'user_id', 'type', 'amount', 'entry_date', 'note'])]
class SavingsEntry extends Model
{
    /** @use HasFactory<SavingsEntryFactory> */
    use HasFactory, HasMicrosecondTimestamps, HasUlids, NotifiesSpaceChanges, SoftDeletes;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'deposit',
    ];

    /**
     * Get the goal the entry was recorded against.
     */
    public function savingsGoal(): BelongsTo
    {
        return $this->belongsTo(SavingsGoal::class);
    }

    /**
     * Get the space the entry belongs to.
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    /**
     * Get the user who recorded the entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }
}
