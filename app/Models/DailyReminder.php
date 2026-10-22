<?php

namespace App\Models;

use App\Models\Concerns\HasMicrosecondTimestamps;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * A reminder that goes out at 08.00 and was already sent: a special date, a
 * member's birthday, or a trip.
 */
#[Fillable(['reminder_key', 'remind_on'])]
class DailyReminder extends Model
{
    use HasMicrosecondTimestamps, HasUlids;
}
