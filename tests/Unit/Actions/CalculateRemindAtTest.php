<?php

use App\Actions\CalculateRemindAt;

it('converts the due date and time from the space timezone to UTC', function (string $timezone, string $expected) {
    $remindAt = (new CalculateRemindAt)->handle('2026-10-09', '17:00', $timezone);

    expect($remindAt->toJSON())->toBe($expected);
})->with([
    'Jakarta, seven hours ahead' => ['Asia/Jakarta', '2026-10-09T10:00:00.000000Z'],
    'Makassar, eight hours ahead' => ['Asia/Makassar', '2026-10-09T09:00:00.000000Z'],
    'Tokyo, nine hours ahead' => ['Asia/Tokyo', '2026-10-09T08:00:00.000000Z'],
]);

it('reminds at 08.00 in the space timezone when the task has no due time', function () {
    $remindAt = (new CalculateRemindAt)->handle('2026-10-09', null, 'Asia/Jakarta');

    expect($remindAt->toJSON())->toBe('2026-10-09T01:00:00.000000Z');
});

it('accepts a due time that carries seconds, as the database returns it', function () {
    $remindAt = (new CalculateRemindAt)->handle('2026-10-09', '17:00:00', 'Asia/Jakarta');

    expect($remindAt->toJSON())->toBe('2026-10-09T10:00:00.000000Z');
});

it('has no reminder when the task has no due date', function () {
    $remindAt = (new CalculateRemindAt)->handle(null, '17:00', 'Asia/Jakarta');

    expect($remindAt)->toBeNull();
});
