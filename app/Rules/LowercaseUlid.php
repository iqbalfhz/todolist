<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class LowercaseUlid implements ValidationRule
{
    /**
     * The shape of the ULIDs this API uses: 26 lowercase Crockford characters.
     */
    public const PATTERN = '/^[0-7][0-9a-hjkmnp-tv-z]{25}$/';

    /**
     * Determine whether the given value is a lowercase ULID.
     */
    public static function matches(mixed $value): bool
    {
        return is_string($value) && preg_match(self::PATTERN, $value) === 1;
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::matches($value)) {
            $fail('api.validation.lowercase_ulid')->translate();
        }
    }
}
