<?php

use App\Jobs\RecalculateSpaceReminders;
use App\Models\SpaceInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

describe('show', function () {
    it('shows the profile with every field of the contract', function () {
        $this->travelTo('2026-10-08 10:15:30.123456');
        $user = actingAsApiUser();

        $response = $this->getJson('/api/v1/me');

        $response->assertOk()->assertExactJson([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'color' => 'mawar',
                'timezone' => 'Asia/Jakarta',
                'locale' => 'id',
                'default_currency' => 'IDR',
                'birth_date' => null,
                'notification_prefs' => [
                    'task_reminders' => true,
                    'partner_activity' => true,
                    'savings_activity' => true,
                ],
                'avatar_url' => null,
                'avatar_version' => 0,
                'invitation' => null,
                'created_at' => '2026-10-08T10:15:30.123456Z',
                'updated_at' => '2026-10-08T10:15:30.123456Z',
            ],
        ]);
    });

    it('includes the invitation code that is still active', function () {
        $this->travelTo('2026-10-08 10:16:00');
        $user = actingAsApiUser();
        SpaceInvitation::factory()->for($user, 'creator')->create(['code' => 'K7M2QX']);

        $response = $this->getJson('/api/v1/me');

        $response->assertOk()->assertJsonPath('data.invitation', [
            'code' => 'K7M2QX',
            'expires_at' => '2026-10-10T10:16:00.000000Z',
        ]);
    });

    it('leaves out an invitation that can no longer be used', function (string $state) {
        $user = actingAsApiUser();
        SpaceInvitation::factory()->for($user, 'creator')->{$state}()->create();

        $response = $this->getJson('/api/v1/me');

        $response->assertOk()->assertJsonPath('data.invitation', null);
    })->with(['expired', 'revoked', 'accepted']);

    it('returns 401 when no token is sent', function () {
        $response = $this->getJson('/api/v1/me');

        $response->assertUnauthorized();
    });
});

describe('update', function () {
    it('changes only the fields that were sent', function () {
        $user = actingAsApiUser();

        $response = $this->patchJson('/api/v1/me', ['name' => 'Iqbal']);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Iqbal')
            ->assertJsonPath('data.color', 'mawar');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Iqbal', 'color' => 'mawar']);
    });

    it('stores each editable field', function (string $field, mixed $value) {
        $user = actingAsApiUser();

        $response = $this->patchJson('/api/v1/me', [$field => $value]);

        $response->assertOk()->assertJsonPath("data.{$field}", $value);
        $this->assertDatabaseHas('users', ['id' => $user->id, $field => $value]);
    })->with([
        'a color' => ['color', 'toska'],
        'a default currency' => ['default_currency', 'USD'],
        'a birth date with a year' => ['birth_date', '1998-03-02'],
        'a birth date without a year' => ['birth_date', '--03-02'],
        'a leap day without a year' => ['birth_date', '--02-29'],
    ]);

    it('clears the birth date when null is sent', function () {
        $user = actingAsApiUser();
        $user->update(['birth_date' => '1998-03-02']);

        $response = $this->patchJson('/api/v1/me', ['birth_date' => null]);

        $response->assertOk()->assertJsonPath('data.birth_date', null);
    });

    it('changes only the notification preferences that were sent', function () {
        actingAsApiUser();

        $response = $this->patchJson('/api/v1/me', ['notification_prefs' => ['partner_activity' => false]]);

        $response->assertOk()->assertJsonPath('data.notification_prefs', [
            'task_reminders' => true,
            'partner_activity' => false,
            'savings_activity' => true,
        ]);
    });

    it('ignores read-only and unknown fields', function () {
        $user = actingAsApiUser();

        $response = $this->patchJson('/api/v1/me', [
            'email' => 'baru@contoh.test',
            'avatar_version' => 9,
            'locale' => 'en',
            'tema' => 'anggrek',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.avatar_version', 0)
            ->assertJsonPath('data.locale', 'id');
    });

    it('returns 422 for invalid values', function (array $payload, string $field) {
        actingAsApiUser();

        $response = $this->patchJson('/api/v1/me', $payload);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonValidationErrors([$field]);
    })->with([
        'an empty name' => [['name' => ''], 'name'],
        'a name longer than 50 characters' => [['name' => str_repeat('a', 51)], 'name'],
        'a color outside the palette' => [['color' => 'hijau'], 'color'],
        'a timezone that is not an IANA name' => [['timezone' => 'WIB'], 'timezone'],
        'a currency outside the table' => [['default_currency' => 'GBP'], 'default_currency'],
        'a birth date that is not on the calendar' => [['birth_date' => '--02-30'], 'birth_date'],
        'a birth date in another format' => [['birth_date' => '02/03/1998'], 'birth_date'],
        'a preference that is not a boolean' => [['notification_prefs' => ['task_reminders' => 'ya']], 'notification_prefs.task_reminders'],
    ]);

    it('returns the stored profile as current when validation fails', function () {
        $user = actingAsApiUser();

        $response = $this->patchJson('/api/v1/me', ['name' => str_repeat('a', 51)]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Nama maksimal berisi 50 karakter.')
            ->assertJsonPath('current.id', $user->id)
            ->assertJsonPath('current.name', $user->name);
    });

    it('moves the personal space to the new timezone and queues a reminder recalculation', function () {
        Queue::fake([RecalculateSpaceReminders::class]);
        $user = actingAsApiUser();
        $space = $user->personalSpace();

        $response = $this->patchJson('/api/v1/me', ['timezone' => 'Asia/Makassar']);

        $response->assertOk()->assertJsonPath('data.timezone', 'Asia/Makassar');
        $this->assertDatabaseHas('spaces', ['id' => $space->id, 'timezone' => 'Asia/Makassar']);
        Queue::assertPushed(RecalculateSpaceReminders::class, fn (RecalculateSpaceReminders $job) => $job->spaceId === $space->id);
    });

    it('leaves the shared space in its own timezone when the user moves', function () {
        Queue::fake([RecalculateSpaceReminders::class]);
        $user = actingAsApiUser();
        $sharedSpace = sharedSpaceBetween($user, User::factory()->create());

        $this->patchJson('/api/v1/me', ['timezone' => 'Asia/Makassar']);

        $this->assertDatabaseHas('spaces', ['id' => $sharedSpace->id, 'timezone' => 'Asia/Jakarta']);
    });

    it('bumps every space of the user when the name or color changes, so the partner syncs it', function (array $payload) {
        $this->travelTo('2026-10-08 10:00:00');
        $user = actingAsApiUser();
        $sharedSpace = sharedSpaceBetween($user, User::factory()->create());
        $this->travelTo('2026-10-08 11:00:00.500000');

        $this->patchJson('/api/v1/me', $payload);

        expect($user->personalSpace()->updated_at->toJSON())->toBe('2026-10-08T11:00:00.500000Z')
            ->and($sharedSpace->fresh()->updated_at->toJSON())->toBe('2026-10-08T11:00:00.500000Z');
    })->with([
        'the name' => [['name' => 'Iqbal']],
        'the color' => [['color' => 'ungu']],
    ]);

    it('does not bump the spaces when only private fields change', function () {
        $this->travelTo('2026-10-08 10:00:00');
        $user = actingAsApiUser();
        $this->travelTo('2026-10-08 11:00:00');

        $this->patchJson('/api/v1/me', ['notification_prefs' => ['task_reminders' => false], 'default_currency' => 'USD']);

        expect($user->personalSpace()->updated_at->toJSON())->toBe('2026-10-08T10:00:00.000000Z');
    });

    it('returns 401 when no token is sent', function () {
        $response = $this->patchJson('/api/v1/me', ['name' => 'Iqbal']);

        $response->assertUnauthorized();
    });
});
