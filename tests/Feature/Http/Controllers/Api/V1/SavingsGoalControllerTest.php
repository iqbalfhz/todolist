<?php

use App\Enums\PushType;
use App\Jobs\SendPushNotification;
use App\Models\SavingsEntry;
use App\Models\SavingsGoal;
use App\Models\Space;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

describe('store', function () {
    it('creates the goal with the ID from the phone and returns 201', function () {
        $this->travelTo('2026-10-08 11:00:00.123456');
        $user = actingAsApiUser();
        $space = $user->personalSpace();
        $id = newId();

        $response = $this->putJson("/api/v1/savings-goals/{$id}", [
            'space_id' => $space->id,
            'name' => 'Dana Liburan',
            'emoji' => '✈️',
            'target_amount' => 10000000,
            'currency' => 'IDR',
            'target_date' => '2027-06-30',
            'reminder_frequency' => 'weekly',
            'reminder_day' => 5,
        ]);

        $response->assertCreated()->assertExactJson([
            'data' => [
                'id' => $id,
                'space_id' => $space->id,
                'name' => 'Dana Liburan',
                'emoji' => '✈️',
                'target_amount' => 10000000,
                'currency' => 'IDR',
                'target_date' => '2027-06-30',
                'reminder_frequency' => 'weekly',
                'reminder_day' => 5,
                'achieved_at' => null,
                'archived_at' => null,
                'created_by' => $user->id,
                'created_at' => '2026-10-08T11:00:00.123456Z',
                'updated_at' => '2026-10-08T11:00:00.123456Z',
                'deleted_at' => null,
            ],
        ]);
    });

    it('accepts the largest amount and leaves optional fields empty', function () {
        $user = actingAsApiUser();

        $response = $this->putJson('/api/v1/savings-goals/'.newId(), [
            'space_id' => $user->personalSpace()->id,
            'name' => 'Rumah',
            'target_amount' => 1_000_000_000_000_000,
            'currency' => 'USD',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.target_amount', 1_000_000_000_000_000)
            ->assertJsonPath('data.emoji', null)
            ->assertJsonPath('data.target_date', null);
    });

    it('returns 200 with the stored goal and changes nothing when the PUT is repeated', function () {
        $user = actingAsApiUser();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create(['name' => 'Dana Liburan']);

        $response = $this->putJson("/api/v1/savings-goals/{$goal->id}", [
            'space_id' => $goal->space_id,
            'name' => 'Nama dari kiriman ulang',
            'target_amount' => 5,
            'currency' => 'USD',
        ]);

        $response->assertOk()->assertJsonPath('data.name', 'Dana Liburan');
        $this->assertDatabaseHas('savings_goals', ['id' => $goal->id, 'name' => 'Dana Liburan', 'currency' => 'IDR']);
    });

    it('returns 410 with the resource_deleted code when the ID belongs to a deleted goal', function () {
        $user = actingAsApiUser();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create();
        $goal->delete();

        $response = $this->putJson("/api/v1/savings-goals/{$goal->id}", [
            'space_id' => $goal->space_id,
            'name' => 'Dana Liburan',
            'target_amount' => 10000000,
            'currency' => 'IDR',
        ]);

        $response->assertGone()->assertJsonPath('code', 'resource_deleted');
    });

    it('returns 409 with the id_conflict code when the ID is used in another space', function (Closure $existingSpace) {
        $user = actingAsApiUser();
        $goal = SavingsGoal::factory()->for($existingSpace($user))->create();

        $response = $this->putJson("/api/v1/savings-goals/{$goal->id}", [
            'space_id' => $user->personalSpace()->id,
            'name' => 'Dana Liburan',
            'target_amount' => 10000000,
            'currency' => 'IDR',
        ]);

        $response->assertConflict()->assertJsonPath('code', 'id_conflict');
        $this->assertDatabaseCount('savings_goals', 1);
    })->with([
        'the space shared with the partner' => [fn (User $user) => sharedSpaceBetween($user, User::factory()->create())],
        'a space of another couple' => [fn () => Space::factory()->create()],
    ]);

    it('returns 404 when the space is not one of the user\'s', function () {
        actingAsApiUser();

        $response = $this->putJson('/api/v1/savings-goals/'.newId(), [
            'space_id' => Space::factory()->create()->id,
            'name' => 'Dana Liburan',
            'target_amount' => 10000000,
            'currency' => 'IDR',
        ]);

        $response->assertNotFound()->assertJsonPath('code', 'not_found');
        $this->assertDatabaseEmpty('savings_goals');
    });

    it('returns 422 for invalid values', function (array $overrides, string $field) {
        $user = actingAsApiUser();

        $response = $this->putJson('/api/v1/savings-goals/'.newId(), [
            'space_id' => $user->personalSpace()->id,
            'name' => 'Dana Liburan',
            'target_amount' => 10000000,
            'currency' => 'IDR',
            ...$overrides,
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonPath('current', null)
            ->assertJsonValidationErrors([$field]);
        $this->assertDatabaseEmpty('savings_goals');
    })->with([
        'no space' => [['space_id' => null], 'space_id'],
        'no name' => [['name' => ''], 'name'],
        'a name longer than 50 characters' => [['name' => str_repeat('a', 51)], 'name'],
        'a target of zero' => [['target_amount' => 0], 'target_amount'],
        'a target with decimals' => [['target_amount' => 10.5], 'target_amount'],
        'a target above 10^15' => [['target_amount' => 1_000_000_000_000_001], 'target_amount'],
        'no currency' => [['currency' => null], 'currency'],
        'a currency outside the table' => [['currency' => 'GBP'], 'currency'],
        'a target date that is not on the calendar' => [['target_date' => '2027-02-30'], 'target_date'],
        'a reminder frequency outside the list' => [['reminder_frequency' => 'daily', 'reminder_day' => 1], 'reminder_frequency'],
        'a reminder frequency without a day' => [['reminder_frequency' => 'weekly'], 'reminder_day'],
        'a weekly reminder on day 8' => [['reminder_frequency' => 'weekly', 'reminder_day' => 8], 'reminder_day'],
        'a monthly reminder on day 29' => [['reminder_frequency' => 'monthly', 'reminder_day' => 29], 'reminder_day'],
    ]);

    it('returns 401 when no token is sent', function () {
        $response = $this->putJson('/api/v1/savings-goals/'.newId(), []);

        $response->assertUnauthorized();
    });
});

describe('update', function () {
    it('changes only the fields that were sent', function () {
        $user = actingAsApiUser();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create(['name' => 'Dana Liburan', 'target_amount' => 1_000_000]);

        $response = $this->patchJson("/api/v1/savings-goals/{$goal->id}", ['name' => 'Liburan ke Jepang', 'target_date' => '2027-06-30']);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Liburan ke Jepang')
            ->assertJsonPath('data.target_date', '2027-06-30')
            ->assertJsonPath('data.target_amount', 1_000_000);
    });

    it('checks a new reminder day against the frequency already stored', function (array $stored, array $payload, int $status, ?int $day) {
        $user = actingAsApiUser();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create($stored);

        $response = $this->patchJson("/api/v1/savings-goals/{$goal->id}", $payload);

        $response->assertStatus($status);
        expect($goal->fresh()->reminder_day)->toBe($day);
    })->with([
        'a weekly goal moved to day 9' => [['reminder_frequency' => 'weekly', 'reminder_day' => 5], ['reminder_day' => 9], 422, 5],
        'a weekly goal moved to day 2' => [['reminder_frequency' => 'weekly', 'reminder_day' => 5], ['reminder_day' => 2], 200, 2],
        'a monthly goal moved to the 25th' => [['reminder_frequency' => 'monthly', 'reminder_day' => 1], ['reminder_day' => 25], 200, 25],
        'a weekly goal switched to monthly on day 9' => [['reminder_frequency' => 'weekly', 'reminder_day' => 5], ['reminder_frequency' => 'monthly', 'reminder_day' => 9], 200, 9],
    ]);

    it('clears the reminder day when the frequency is cleared', function () {
        $user = actingAsApiUser();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create(['reminder_frequency' => 'weekly', 'reminder_day' => 5]);

        $response = $this->patchJson("/api/v1/savings-goals/{$goal->id}", ['reminder_frequency' => null]);

        $response->assertOk()
            ->assertJsonPath('data.reminder_frequency', null)
            ->assertJsonPath('data.reminder_day', null);
    });

    it('keeps the currency the goal was created with', function () {
        $user = actingAsApiUser();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create(['currency' => 'IDR']);

        $response = $this->patchJson("/api/v1/savings-goals/{$goal->id}", ['currency' => 'USD', 'name' => 'Dana Liburan']);

        $response->assertOk()->assertJsonPath('data.currency', 'IDR');
    });

    it('archives the goal and takes it out of the archive again', function (Closure $goal, mixed $archivedAt, ?string $expected) {
        $this->travelTo('2026-10-08 12:00:00');
        $user = actingAsApiUser();
        $goal = $goal($user);

        $response = $this->patchJson("/api/v1/savings-goals/{$goal->id}", ['archived_at' => $archivedAt]);

        $response->assertOk()->assertJsonPath('data.archived_at', $expected);
    })->with([
        'archive' => [fn (User $user) => SavingsGoal::factory()->for($user->personalSpace())->create(), '2026-10-08T17:30:00+07:00', '2026-10-08T10:30:00.000000Z'],
        'restore' => [fn (User $user) => SavingsGoal::factory()->for($user->personalSpace())->archived()->create(), null, null],
    ]);

    it('marks the goal achieved when the target drops to what was already saved', function () {
        Queue::fake([SendPushNotification::class]);
        $this->travelTo('2026-10-08 12:00:00');
        [$user, $partner, $space] = actingAsPartnered();
        $goal = SavingsGoal::factory()->for($space)->create(['name' => 'Dana Liburan', 'target_amount' => 1_000_000]);
        SavingsEntry::factory()->for($goal)->create(['amount' => 400_000]);

        $response = $this->patchJson("/api/v1/savings-goals/{$goal->id}", ['target_amount' => 400_000]);

        $response->assertOk()->assertJsonPath('data.achieved_at', '2026-10-08T12:00:00.000000Z');
        Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->userId === $partner->id
            && $job->type === PushType::SavingsAchieved
            && $job->title === 'Dana Liburan'
            && $job->savingsGoalId === $goal->id);
    });

    it('clears the achievement when the target rises above what was saved', function () {
        $user = actingAsApiUser();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create([
            'target_amount' => 400_000,
            'achieved_at' => now(),
            'achieved_notified_at' => now(),
        ]);
        SavingsEntry::factory()->for($goal)->create(['amount' => 400_000]);

        $response = $this->patchJson("/api/v1/savings-goals/{$goal->id}", ['target_amount' => 1_000_000]);

        $response->assertOk()->assertJsonPath('data.achieved_at', null);
    });

    it('returns 404 with the not_found code for a goal the user cannot reach', function (Closure $id) {
        actingAsApiUser();

        $response = $this->patchJson('/api/v1/savings-goals/'.$id(), ['name' => 'Dana Liburan']);

        $response->assertNotFound()->assertJsonPath('code', 'not_found')->assertJsonPath('current', null);
    })->with([
        'a goal in the space of another couple' => [fn () => SavingsGoal::factory()->create()->id],
        'an ID that was never created' => [fn () => newId()],
    ]);

    it('returns 410 with the resource_deleted code for a deleted goal', function () {
        $user = actingAsApiUser();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create();
        $goal->delete();

        $response = $this->patchJson("/api/v1/savings-goals/{$goal->id}", ['name' => 'Dana Liburan']);

        $response->assertGone()->assertJsonPath('code', 'resource_deleted');
    });

    it('returns 422 with the stored goal as current for invalid values', function (array $payload, string $field) {
        $user = actingAsApiUser();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create(['name' => 'Dana Liburan']);

        $response = $this->patchJson("/api/v1/savings-goals/{$goal->id}", $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([$field])
            ->assertJsonPath('current.name', 'Dana Liburan');
    })->with([
        'an empty name' => [['name' => ''], 'name'],
        'a target of zero' => [['target_amount' => 0], 'target_amount'],
        'an archive time without an offset' => [['archived_at' => '2026-10-08T10:30:00'], 'archived_at'],
    ]);

    it('returns 401 when no token is sent', function () {
        $response = $this->patchJson('/api/v1/savings-goals/'.newId(), ['name' => 'Dana Liburan']);

        $response->assertUnauthorized();
    });
});

describe('destroy', function () {
    it('soft deletes the goal with its entries and bumps their updated_at', function () {
        $this->travelTo('2026-10-08 10:00:00');
        $user = actingAsApiUser();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create();
        $entry = SavingsEntry::factory()->for($goal)->create();
        $this->travelTo('2026-10-08 11:00:00.250000');

        $response = $this->deleteJson("/api/v1/savings-goals/{$goal->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted($goal);
        $this->assertSoftDeleted($entry);
        expect($entry->fresh()->updated_at->toJSON())->toBe('2026-10-08T11:00:00.250000Z');
    });

    it('unlinks the trips saved up with the goal and bumps their updated_at', function () {
        $this->travelTo('2026-10-22 10:00:00');
        $user = actingAsApiUser();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create();
        $trip = Trip::factory()->for($user->personalSpace())->create(['savings_goal_id' => $goal->id]);
        $otherTrip = Trip::factory()->for($user->personalSpace())->create(['savings_goal_id' => SavingsGoal::factory()->for($user->personalSpace())]);
        $this->travelTo('2026-10-22 11:00:00.250000');

        $this->deleteJson("/api/v1/savings-goals/{$goal->id}");

        expect($trip->fresh())
            ->savings_goal_id->toBeNull()
            ->updated_at->toJSON()->toBe('2026-10-22T11:00:00.250000Z');
        expect($otherTrip->fresh()->savings_goal_id)->not->toBeNull();
    });

    it('leaves the entries of other goals alone', function () {
        $user = actingAsApiUser();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create();
        $otherEntry = SavingsEntry::factory()->for(SavingsGoal::factory()->for($user->personalSpace()))->create();

        $this->deleteJson("/api/v1/savings-goals/{$goal->id}");

        $this->assertNotSoftDeleted($otherEntry);
    });

    it('returns 204 again when the delete is repeated', function () {
        $user = actingAsApiUser();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create();
        $goal->delete();

        $response = $this->deleteJson("/api/v1/savings-goals/{$goal->id}");

        $response->assertNoContent();
    });

    it('returns 404 for a goal in the space of another couple', function () {
        actingAsApiUser();
        $goal = SavingsGoal::factory()->create();

        $response = $this->deleteJson("/api/v1/savings-goals/{$goal->id}");

        $response->assertNotFound()->assertJsonPath('code', 'not_found');
        $this->assertNotSoftDeleted($goal);
    });

    it('returns 401 when no token is sent', function () {
        $response = $this->deleteJson('/api/v1/savings-goals/'.newId());

        $response->assertUnauthorized();
    });
});
