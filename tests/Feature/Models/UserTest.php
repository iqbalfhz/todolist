<?php

use App\Models\User;
use Filament\Facades\Filament;

it('uses a lowercase ULID as the primary key', function () {
    $user = User::factory()->create();

    expect($user->id)->toMatch('/^[0-7][0-9a-hjkmnp-tv-z]{25}$/');
});

it('serializes timestamps as ISO 8601 with microseconds after a database round trip', function () {
    $this->travelTo('2026-01-02 03:04:05.678901');

    $user = User::factory()->create();

    expect($user->fresh()->toArray())->toMatchArray([
        'created_at' => '2026-01-02T03:04:05.678901Z',
        'updated_at' => '2026-01-02T03:04:05.678901Z',
    ]);
});

it('allows admin panel access only to the configured admin email', function (string $email, bool $expected) {
    config(['admin.email' => 'admin@contoh.test']);
    $user = User::factory()->make(['email' => $email]);

    $canAccess = $user->canAccessPanel(Filament::getPanel('admin'));

    expect($canAccess)->toBe($expected);
})->with([
    'the admin email' => ['admin@contoh.test', true],
    'the admin email in another letter case' => ['Admin@Contoh.Test', true],
    'another email' => ['orang-lain@contoh.test', false],
    'an email that only starts with the admin email' => ['admin@contoh.test.example', false],
]);

it('denies admin panel access to everyone while no admin email is configured', function (?string $configuredEmail) {
    config(['admin.email' => $configuredEmail]);
    $user = User::factory()->make();

    $canAccess = $user->canAccessPanel(Filament::getPanel('admin'));

    expect($canAccess)->toBeFalse();
})->with([
    'not set' => [null],
    'an empty value' => [''],
]);
