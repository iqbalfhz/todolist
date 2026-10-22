<?php

namespace App\Rules;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Throwable;

class SyncCursor implements ValidationRule
{
    /**
     * The collections a sync page carries, in the order of the contract.
     *
     * @var list<string>
     */
    public const COLLECTIONS = ['spaces', 'task_lists', 'tasks', 'savings_goals', 'savings_entries', 'special_dates', 'trips'];

    /**
     * The format of every time inside a cursor: UTC with microseconds, as MySQL stores it.
     */
    public const TIME_FORMAT = 'Y-m-d H:i:s.u';

    /**
     * The shape of a time written in the format above.
     */
    private const TIME_PATTERN = '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{6}$/';

    /**
     * Pack the state of a sync into the string the phone sends back for the next page.
     *
     * A position is the updated_at and ID of the last row sent from a
     * collection, or null when a sync without since has sent none yet.
     *
     * @param  array{server_time: string, with_deleted: bool, positions: array<string, array{0: string, 1: string}|null>}  $state
     */
    public static function encode(array $state): string
    {
        return rtrim(strtr(base64_encode(json_encode($state, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
    }

    /**
     * Unpack a cursor, or return null when it is not one this API handed out.
     *
     * @return array{server_time: string, with_deleted: bool, positions: array<string, array{0: string, 1: string}|null>}|null
     */
    public static function decode(string $cursor): ?array
    {
        $json = base64_decode(strtr($cursor, '-_', '+/'), true);
        $state = $json === false ? null : json_decode($json, true);

        return is_array($state) && self::isState($state) ? $state : null;
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || self::decode($value) === null) {
            $fail('api.validation.sync_cursor')->translate();
        }
    }

    /**
     * Determine whether the decoded value has every part of a sync state, and nothing else.
     *
     * @param  array<array-key, mixed>  $state
     */
    private static function isState(array $state): bool
    {
        if (array_keys($state) !== ['server_time', 'with_deleted', 'positions']) {
            return false;
        }

        return self::isTime($state['server_time'])
            && is_bool($state['with_deleted'])
            && is_array($state['positions'])
            && array_keys($state['positions']) === self::COLLECTIONS
            && collect($state['positions'])->every(fn (mixed $position): bool => $position === null || self::isPosition($position));
    }

    /**
     * Determine whether the value is the time and ID of a row.
     *
     * The ID is empty when a sync with since has not sent a row of the collection yet.
     */
    private static function isPosition(mixed $position): bool
    {
        return is_array($position)
            && array_keys($position) === [0, 1]
            && self::isTime($position[0])
            && ($position[1] === '' || LowercaseUlid::matches($position[1]));
    }

    /**
     * Determine whether the value is a moment that exists, so MySQL can compare against it.
     */
    private static function isTime(mixed $value): bool
    {
        if (! is_string($value) || preg_match(self::TIME_PATTERN, $value) !== 1) {
            return false;
        }

        try {
            $time = CarbonImmutable::createFromFormat(self::TIME_FORMAT, $value, 'UTC');
        } catch (Throwable) {
            return false;
        }

        return $time->format(self::TIME_FORMAT) === $value;
    }
}
