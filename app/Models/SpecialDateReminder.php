<?php

namespace App\Models;

use App\Models\Concerns\HasMicrosecondTimestamps;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * A reminder about a special date or a member's birthday that was already sent.
 */
#[Fillable(['reminder_key', 'remind_on'])]
class SpecialDateReminder extends Model
{
    use HasMicrosecondTimestamps, HasUlids;
}
