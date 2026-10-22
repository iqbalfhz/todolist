<?php

namespace App\Models;

use App\Enums\SpecialDateType;
use App\Models\Concerns\HasMicrosecondTimestamps;
use Database\Factories\SpecialDateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['space_id', 'type', 'title', 'date', 'created_by'])]
class SpecialDate extends Model
{
    /** @use HasFactory<SpecialDateFactory> */
    use HasFactory, HasMicrosecondTimestamps, HasUlids, SoftDeletes;

    /**
     * Get the space the date belongs to.
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => SpecialDateType::class,
        ];
    }
}
