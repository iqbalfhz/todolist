<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;
use Illuminate\Translation\PotentiallyTranslatedString;
use Throwable;

class Iso8601WithOffset implements ValidationRule
{
    /**
     * An ISO 8601 date-time that ends in Z or a UTC offset.
     */
    private const PATTERN = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d{1,9})?(Z|[+-]\d{2}:\d{2})$/';

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match(self::PATTERN, $value) !== 1 || ! $this->isRealMoment($value)) {
            $fail('api.validation.iso8601_with_offset')->translate();
        }
    }

    /**
     * Determine whether the value names a moment that exists, so 30 February is rejected.
     */
    private function isRealMoment(string $value): bool
    {
        try {
            $moment = Carbon::parse($value);
        } catch (Throwable) {
            return false;
        }

        return $moment->format('Y-m-d\TH:i:s') === substr($value, 0, 19);
    }
}
