<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class Position implements ValidationRule
{
    /**
     * The furthest a position may be from zero.
     *
     * The app spaces positions 1024 apart, so real ones stay far below this.
     * A value such as 1e400 is refused here, before MySQL fails to store an
     * infinite double.
     */
    public const LIMIT = 1_000_000_000_000_000;

    /**
     * Run the validation rule.
     *
     * A value that is not a number is left to the numeric rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value)) {
            return;
        }

        $number = (float) $value;

        if (! is_finite($number) || abs($number) > self::LIMIT) {
            $fail('api.validation.position')->translate();
        }
    }
}
