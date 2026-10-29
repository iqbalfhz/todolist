<?php

namespace App\Models;

use App\Models\Concerns\HasDiscussion;
use App\Models\Concerns\HasMicrosecondTimestamps;
use App\Models\Concerns\NotifiesSpaceChanges;
use Database\Factories\MemoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
    'latitude',
    'longitude',
    'visited_on',
    'story',
    'created_by',
])]
class Memory extends Model
{
    /** @use HasFactory<MemoryFactory> */
    use HasDiscussion, HasFactory, HasMicrosecondTimestamps, HasUlids, NotifiesSpaceChanges, SoftDeletes;

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

    /**
     * Round the latitude to six decimals, as the column stores it.
     */
    protected function latitude(): Attribute
    {
        return Attribute::make(set: fn (mixed $value): ?float => self::roundCoordinate($value));
    }

    /**
     * Round the longitude to six decimals, as the column stores it.
     */
    protected function longitude(): Attribute
    {
        return Attribute::make(set: fn (mixed $value): ?float => self::roundCoordinate($value));
    }

    /**
     * Round a coordinate from the phone, whose GPS gives many more decimals than are kept.
     */
    private static function roundCoordinate(mixed $value): ?float
    {
        return $value === null ? null : round((float) $value, 6);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }
}
