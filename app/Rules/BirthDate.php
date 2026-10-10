<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class BirthDate implements ValidationRule
{
    /**
     * A leap year, so 29 February is accepted when the year is left out.
     */
    private const YEAR_WHEN_OMITTED = 2000;

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/^(\d{4}|-)-(\d{2})-(\d{2})$/', $value, $parts) !== 1) {
            $fail('api.validation.birth_date')->translate();

            return;
        }

        $year = $parts[1] === '-' ? self::YEAR_WHEN_OMITTED : (int) $parts[1];

        if (! checkdate((int) $parts[2], (int) $parts[3], $year)) {
            $fail('api.validation.birth_date')->translate();
        }
    }
}
