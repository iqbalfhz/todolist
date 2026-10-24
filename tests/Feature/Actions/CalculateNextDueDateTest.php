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
