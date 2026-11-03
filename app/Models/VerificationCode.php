<?php

namespace App\Models;

use App\Enums\VerificationPurpose;
use App\Models\Concerns\HasMicrosecondTimestamps;
use Database\Factories\VerificationCodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A 6-digit code emailed to a user to reset the password, verify the email,
 * or change the email, in which case the new address is kept with it.
 */
#[Fillable(['user_id', 'purpose', 'new_email', 'code_hash', 'attempts', 'expires_at'])]
class VerificationCode extends Model
{
    /** @use HasFactory<VerificationCodeFactory> */
    use HasFactory, HasMicrosecondTimestamps, HasUlids;

    /**
     * How many digits a code has.
     */
    public const LENGTH = 6;

    /**
     * How long a code works after it is sent.
     */
    public const VALID_FOR_MINUTES = 60;

    /**
     * How many wrong tries end a code.
     */
    public const MAX_ATTEMPTS = 5;

    /**
     * Get the keyed hash stored in place of the code.
     *
     * The key is the application key, so the codes cannot be worked out from
     * a copy of the database alone, although a code has only a million values.
     */
    public static function hash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }

    /**
     * Get the user the code was sent to.
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
            'purpose' => VerificationPurpose::class,
            'attempts' => 'integer',
            'expires_at' => 'datetime',
        ];
    }
}
