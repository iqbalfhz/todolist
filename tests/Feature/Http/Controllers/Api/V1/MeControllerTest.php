<?php

use App\Actions\DeleteAccount;
use App\Enums\PushType;
use App\Jobs\RecalculateSpaceReminders;
use App\Jobs\SendPushNotification;
use App\Models\Comment;
use App\Models\DeviceToken;
use App\Models\Memory;
use App\Models\MemoryPhoto;
use App\Models\SpaceInvitation;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

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
                    'special_dates' => true,
                    'trip_reminders' => true,
                    'savings_reminders' => true,
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
            'special_dates' => true,
            'trip_reminders' => true,
            'savings_reminders' => true,
        ]);
    });

    it('turns the special date or trip reminders off', function (string $preference) {
        actingAsApiUser();

        $response = $this->patchJson('/api/v1/me', ['notification_prefs' => [$preference => false]]);

        $response->assertOk()->assertJsonPath("data.notification_prefs.{$preference}", false);
    })->with(['special_dates', 'trip_reminders', 'savings_reminders']);

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

    it('bumps every space of the user when the name, color, or birth date changes, so the partner syncs it', function (array $payload) {
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
        'the birth date' => [['birth_date' => '--03-02']],
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

describe('destroy', function () {
    it('deletes the account with its password, with everything it holds, and returns 204', function () {
        Storage::fake('r2');
        $user = actingAsApiUser();
        $personalSpace = $user->personalSpace();
        $task = Task::factory()->for($personalSpace->taskLists()->sole())->create();
        $photo = MemoryPhoto::factory()->for(Memory::factory()->for($personalSpace))->create();
        Storage::disk('r2')->put($photo->path, 'foto');
        $user->forceFill(['avatar_path' => "avatars/{$user->id}/foto.jpg"])->save();
        Storage::disk('r2')->put("avatars/{$user->id}/foto.jpg", 'foto');
        DeviceToken::factory()->for($user)->create();
        $user->createToken('hp-lain');
        $invitation = SpaceInvitation::factory()->for($user, 'creator')->create();

        $response = $this->deleteJson('/api/v1/me', ['password' => 'password']);

        $response->assertNoContent();
        $this->assertModelMissing($user);
        $this->assertModelMissing($personalSpace);
        $this->assertModelMissing($task);
        $this->assertModelMissing($photo);
        $this->assertModelMissing($invitation);
        $this->assertDatabaseMissing('device_tokens', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
        Storage::disk('r2')->assertMissing([$photo->path, "avatars/{$user->id}/foto.jpg"]);
    });

    it('deletes an account linked to Google with an ID token of that Google account', function () {
        Storage::fake('r2');
        config(['services.google.client_id' => GOOGLE_TEST_CLIENT_ID]);
        fakeGoogleKeys();
        $user = User::factory()->withPersonalSpace()->create(['google_id' => '110248495921238986420', 'password' => null]);
        $this->actingAs($user, 'sanctum');

        $response = $this->deleteJson('/api/v1/me', ['id_token' => googleIdToken()]);

        $response->assertNoContent();
        $this->assertModelMissing($user);
    });

    it('closes the shared space, deletes what it held, hands it to the partner, and tells the partner', function () {
        Storage::fake('r2');
        Queue::fake([SendPushNotification::class]);
        [$user, $partner, $sharedSpace] = actingAsPartnered();
        $user->update(['name' => 'Iqbal']);
        $task = Task::factory()->for(TaskList::factory()->for($sharedSpace))->hasAttached($partner, [], 'assignees')->create();
        $comment = Comment::factory()->on($task)->create(['user_id' => $user->id]);
        $photo = MemoryPhoto::factory()->for(Memory::factory()->for($sharedSpace))->create();
        Storage::disk('r2')->put($photo->path, 'foto');

        $this->deleteJson('/api/v1/me', ['password' => 'password'])->assertNoContent();

        $this->assertSoftDeleted($sharedSpace);
        expect($sharedSpace->fresh()->owner_id)->toBe($partner->id);
        $this->assertModelMissing($task);
        $this->assertModelMissing($comment);
        $this->assertModelMissing($photo);
        Storage::disk('r2')->assertMissing($photo->path);
        $this->assertDatabaseHas('space_members', ['space_id' => $sharedSpace->id, 'user_id' => $partner->id]);
        Queue::assertPushedTimes(SendPushNotification::class, 1);
        Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->userId === $partner->id
            && $job->type === PushType::PartnerLeft
            && $job->title === 'Ruang Bersama'
            && $job->body === 'Iqbal menghapus akunnya, jadi Ruang Bersama kalian ditutup.'
            && $job->spaceId === $sharedSpace->id);
    });

    it('empties a shared space that was closed before, without telling the former partner again', function () {
        Storage::fake('r2');
        Queue::fake([SendPushNotification::class]);
        $this->travelTo('2026-10-20 08:00:00');
        $user = actingAsApiUser();
        $formerPartner = User::factory()->withPersonalSpace()->create();
        $closedSpace = sharedSpaceBetween($user, $formerPartner);
        $list = TaskList::factory()->for($closedSpace)->create();
        $closedSpace->delete();
        $this->travelTo('2026-10-25 08:00:00');

        $this->deleteJson('/api/v1/me', ['password' => 'password'])->assertNoContent();

        $this->assertModelMissing($list);
        $this->assertSoftDeleted($closedSpace);
        expect($closedSpace->fresh()->owner_id)->toBe($formerPartner->id);
        Queue::assertNothingPushed();
    });

    it('deletes a shared space whose partner already deleted their account', function () {
        Storage::fake('r2');
        Queue::fake([SendPushNotification::class]);
        [$user, $partner, $sharedSpace] = actingAsPartnered();
        app(DeleteAccount::class)->handle($partner);

        $this->deleteJson('/api/v1/me', ['password' => 'password'])->assertNoContent();

        $this->assertModelMissing($sharedSpace);
        $this->assertModelMissing($user);
    });

    it('sends the closed shared space to the partner through sync, with only the partner in it', function () {
        Storage::fake('r2');
        Queue::fake([SendPushNotification::class]);
        $this->travelTo('2026-10-30 08:00:00');
        [, $partner, $sharedSpace] = actingAsPartnered();
        $this->travelTo('2026-10-30 09:00:00.500000');
        $this->deleteJson('/api/v1/me', ['password' => 'password'])->assertNoContent();

        $response = $this->actingAs($partner, 'sanctum')
            ->getJson('/api/v1/sync?'.http_build_query(['since' => '2026-10-30T08:30:00Z']));

        $response->assertOk()
            ->assertJsonPath('data.spaces.*.id', [$sharedSpace->id])
            ->assertJsonPath('data.spaces.0.deleted_at', '2026-10-30T09:00:00.500000Z')
            ->assertJsonPath('data.spaces.0.owner_id', $partner->id)
            ->assertJsonPath('data.spaces.0.members.*.id', [$partner->id]);
    });

    it('lets the same email sign up again as a new account', function () {
        Storage::fake('r2');
        $user = actingAsApiUser();
        $this->deleteJson('/api/v1/me', ['password' => 'password'])->assertNoContent();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Iqbal',
            'email' => $user->email,
            'password' => 'kata-sandi-baru',
            'device_name' => 'Pixel 9',
        ]);

        $response->assertCreated();
        expect($response->json('data.user.id'))->not->toBe($user->id);
    });

    it('returns 422 with the invalid_credentials code for a wrong password and deletes nothing', function (array $attributes) {
        $user = User::factory()->withPersonalSpace()->create($attributes);
        $this->actingAs($user, 'sanctum');

        $response = $this->deleteJson('/api/v1/me', ['password' => 'bukan-kata-sandinya']);

        $response->assertUnprocessable()->assertExactJson([
            'message' => 'Kata sandi salah.',
            'code' => 'invalid_credentials',
        ]);
        $this->assertModelExists($user);
    })->with([
        'an account with a password' => [[]],
        'an account from Google without a password' => [['google_id' => '110248495921238986420', 'password' => null]],
    ]);

    it('returns 422 with the google_token_invalid code for a token of a Google account that is not linked', function (?string $googleId) {
        config(['services.google.client_id' => GOOGLE_TEST_CLIENT_ID]);
        fakeGoogleKeys();
        $user = User::factory()->withPersonalSpace()->create(['google_id' => $googleId]);
        $this->actingAs($user, 'sanctum');

        $response = $this->deleteJson('/api/v1/me', ['id_token' => googleIdToken()]);

        $response->assertUnprocessable()->assertExactJson([
            'message' => 'Pilih akun Google yang tersambung ke akun ini.',
            'code' => 'google_token_invalid',
        ]);
        $this->assertModelExists($user);
    })->with([
        'an account linked to another Google account' => ['209876543210987654321'],
        'an account with no Google account' => [null],
    ]);

    it('returns 422 with the validation_failed code when neither the password nor a Google token is sent', function () {
        $user = actingAsApiUser();

        $response = $this->deleteJson('/api/v1/me', []);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonPath('errors.password.0', 'Kata sandi wajib diisi bila tidak terdapat token Google.');
        $this->assertModelExists($user);
    });

    it('returns 429 after five attempts by the same account within a minute', function () {
        actingAsApiUser();
        foreach (range(1, 5) as $attempt) {
            $this->deleteJson('/api/v1/me', ['password' => 'tebakan-salah']);
        }

        $response = $this->deleteJson('/api/v1/me', ['password' => 'tebakan-salah']);

        $response->assertTooManyRequests()->assertJsonPath('code', 'too_many_requests');
    });

    it('returns 401 when no token is sent to delete the account', function () {
        $response = $this->deleteJson('/api/v1/me', ['password' => 'password']);

        $response->assertUnauthorized();
    });
});
