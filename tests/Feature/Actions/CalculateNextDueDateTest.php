<?php

use App\Actions\CalculateNextDueDate;
use App\Enums\RepeatFrequency;

it('returns the first occurrence after the given day, counted from the anchor', function (RepeatFrequency $frequency, int $interval, string $anchor, string $after, string $expected) {
    $next = app(CalculateNextDueDate::class)->handle($frequency, $interval, $anchor, $after);

    expect($next)->toBe($expected);
})->with([
    'daily, the day after' => [RepeatFrequency::Daily, 1, '2026-10-20', '2026-10-20', '2026-10-21'],
    'daily, skipping the missed days' => [RepeatFrequency::Daily, 1, '2026-10-20', '2026-10-24', '2026-10-25'],
    'every 3 days' => [RepeatFrequency::Daily, 3, '2026-10-20', '2026-10-24', '2026-10-26'],
    'weekly, the same weekday' => [RepeatFrequency::Weekly, 1, '2026-10-17', '2026-10-17', '2026-10-24'],
    'every 2 weeks, keeping the weeks of the series' => [RepeatFrequency::Weekly, 2, '2026-10-03', '2026-10-17', '2026-10-31'],
    'monthly, the 31st in February' => [RepeatFrequency::Monthly, 1, '2027-01-31', '2027-01-31', '2027-02-28'],
    'monthly, back to the 31st after February' => [RepeatFrequency::Monthly, 1, '2027-01-31', '2027-02-28', '2027-03-31'],
    'monthly, later in the same month' => [RepeatFrequency::Monthly, 1, '2027-01-31', '2027-03-15', '2027-03-31'],
    'monthly, the 30th in a leap February' => [RepeatFrequency::Monthly, 1, '2028-01-30', '2028-01-30', '2028-02-29'],
    'every 3 months' => [RepeatFrequency::Monthly, 3, '2027-01-15', '2027-05-20', '2027-07-15'],
    'yearly, 29 February in a common year' => [RepeatFrequency::Yearly, 1, '2028-02-29', '2028-02-29', '2029-02-28'],
    'yearly, back to 29 February in a leap year' => [RepeatFrequency::Yearly, 1, '2028-02-29', '2031-03-01', '2032-02-29'],
]);

it('returns the first chosen weekday after the given day, in the weeks counted from the week of the anchor', function (int $interval, array $days, string $anchor, string $after, string $expected) {
    $next = app(CalculateNextDueDate::class)->handle(RepeatFrequency::Weekly, $interval, $anchor, $after, $days);

    expect($next)->toBe($expected);
})->with([
    'Tuesday and Friday, the Friday after a Tuesday' => [1, [2, 5], '2026-11-03', '2026-11-03', '2026-11-06'],
    'Tuesday and Friday, the Tuesday of the next week after a Friday' => [1, [2, 5], '2026-11-03', '2026-11-06', '2026-11-10'],
    'one day other than the weekday of the anchor' => [1, [5], '2026-11-03', '2026-11-03', '2026-11-06'],
    'a due date on a day that was not chosen' => [1, [2, 5], '2026-11-04', '2026-11-04', '2026-11-06'],
    'every 2 weeks, skipping the week between' => [2, [2, 5], '2026-11-03', '2026-11-06', '2026-11-17'],
    'every 2 weeks, completed late in the week between' => [2, [2, 5], '2026-11-03', '2026-11-12', '2026-11-17'],
    'every 2 weeks, completed after the last chosen day of a week of the series' => [2, [2, 5], '2026-11-03', '2026-11-21', '2026-12-01'],
    'every 3 weeks, counted from the anchor rather than the given day' => [3, [3], '2026-11-04', '2026-11-12', '2026-11-25'],
    'weeks run from Monday to Sunday' => [2, [1, 7], '2026-11-02', '2026-11-02', '2026-11-08'],
    'Monday after the Sunday of a week of the series' => [2, [1, 7], '2026-11-02', '2026-11-08', '2026-11-16'],
    'a day before the week of the anchor' => [1, [2, 5], '2026-11-10', '2026-11-03', '2026-11-10'],
    'across the end of the year' => [1, [1, 5], '2026-12-28', '2027-01-01', '2027-01-04'],
]);

it('keeps the weekday of the anchor without chosen weekdays', function (?array $days) {
    $next = app(CalculateNextDueDate::class)->handle(RepeatFrequency::Weekly, 1, '2026-11-03', '2026-11-03', $days);

    expect($next)->toBe('2026-11-10');
})->with([
    'no weekdays' => [null],
    'an empty list' => [[]],
]);
