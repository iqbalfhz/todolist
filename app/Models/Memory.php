<?php

namespace App\Models;

use App\Models\Concerns\HasMicrosecondTimestamps;
use App\Models\Concerns\NotifiesSpaceChanges;
use Database\Factories\MemoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'space_id',
    'trip_id',
    'title',
    'place_name',
    'visited_on',
    'story',
    'created_by',
])]
class Memory extends Model
{
    /** @use HasFactory<MemoryFactory> */
    use HasFactory, HasMicrosecondTimestamps, HasUlids, NotifiesSpaceChanges, SoftDeletes;

    /**
     * Get the space the memory belongs to.
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    /**
     * Get the trip the memory was saved from.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the photos of the memory.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(MemoryPhoto::class);
    }
}
