<?php

namespace App\Models;

use App\Enums\DevicePlatform;
use App\Models\Concerns\HasMicrosecondTimestamps;
use Database\Factories\DeviceTokenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'token', 'platform'])]
class DeviceToken extends Model
{
    /** @use HasFactory<DeviceTokenFactory> */
    use HasFactory, HasMicrosecondTimestamps, HasUlids;

    /**
     * Get the user the device belongs to.
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
            'platform' => DevicePlatform::class,
        ];
    }
}
