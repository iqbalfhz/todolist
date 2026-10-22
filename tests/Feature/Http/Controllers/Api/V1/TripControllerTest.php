<?php

use App\Models\SavingsGoal;
use App\Models\Space;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\Trip;
use App\Models\User;

describe('store', function () {
    it('creates the trip with the ID from the phone and returns 201', function () {
        $this->travelTo('2026-10-22 10:00:00.123456');
        [$user, $partner, $space] = actingAsPartnered();
        $goal = SavingsGoal::factory()->for($space)->create();
        $id = newId();

        $response = $this->putJson("/api/v1/trips/{$id}", [
            'space_id' => $space->id,
            'title' => 'Liburan ke Bali',
            'destination' => 'Bali',
            'start_date' => '2027-06-20',
            'end_date' => '2027-06-25',
            'notes' => 'Bawa sunblock',
            'savings_goal_id' => $goal->id,
        ]);

        $response->assertCreated()->assertExactJson([
            'data' => [
                'id' => $id,
                'space_id' => $space->id,
                'title' => 'Liburan ke Bali',
                'destination' => 'Bali',
                'start_date' => '2027-06-20',
                'end_date' => '2027-06-25',
                'notes' => 'Bawa sunblock',
                'savings_goal_id' => $goal->id,
                'created_by' => $user->id,
                'created_at' => '2026-10-22T10:00:00.123456Z',
                'updated_at' => '2026-10-22T10:00:00.123456Z',
                'deleted_at' => null,
            ],
        ]);
    });

    it('accepts a trip with only a title, for a plan without dates', function () {
        $user = actingAsApiUser();

        $response = $this->putJson('/api/v1/trips/'.newId(), [
            'space_id' => $user->personalSpace()->id,
            'title' => 'Suatu hari ke Jepang',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.destination', null)
            ->assertJsonPath('data.start_date', null)
            ->assertJsonPath('data.end_date', null)
            ->assertJsonPath('data.savings_goal_id', null);
    });

    it('stores no link when the goal was already deleted, and keeps the rest of the trip', function () {
        $user = actingAsApiUser();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create();
        $goal->delete();

        $response = $this->putJson('/api/v1/trips/'.newId(), [
            'space_id' => $user->personalSpace()->id,
            'title' => 'Liburan ke Bali',
            'savings_goal_id' => $goal->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Liburan ke Bali')
            ->assertJsonPath('data.savings_goal_id', null);
    });

    it('returns 200 with the stored trip and changes nothing when the PUT is repeated', function () {
        $user = actingAsApiUser();
        $trip = Trip::factory()->for($user->personalSpace())->create(['title' => 'Liburan ke Bali']);

        $response = $this->putJson("/api/v1/trips/{$trip->id}", [
            'space_id' => $trip->space_id,
            'title' => 'Judul dari kiriman ulang',
        ]);

        $response->assertOk()->assertJsonPath('data.title', 'Liburan ke Bali');
        $this->assertDatabaseHas('trips', ['id' => $trip->id, 'title' => 'Liburan ke Bali']);
    });

    it('returns 410 with the resource_deleted code when the ID belongs to a deleted trip', function () {
        $user = actingAsApiUser();
        $trip = Trip::factory()->for($user->personalSpace())->create();
        $trip->delete();

        $response = $this->putJson("/api/v1/trips/{$trip->id}", [
            'space_id' => $trip->space_id,
            'title' => 'Liburan ke Bali',
        ]);

        $response->assertGone()->assertJsonPath('code', 'resource_deleted');
    });

    it('returns 409 with the id_conflict code when the ID is used in another space', function (Closure $existingSpace) {
        $user = actingAsApiUser();
        $trip = Trip::factory()->for($existingSpace($user))->create();

        $response = $this->putJson("/api/v1/trips/{$trip->id}", [
            'space_id' => $user->personalSpace()->id,
            'title' => 'Liburan ke Bali',
        ]);

        $response->assertConflict()->assertJsonPath('code', 'id_conflict');
        $this->assertDatabaseCount('trips', 1);
    })->with([
        'the space shared with the partner' => [fn (User $user) => sharedSpaceBetween($user, User::factory()->create())],
        'a space of another couple' => [fn () => Space::factory()->create()],
    ]);

    it('returns 404 when the space is not one of the user\'s, including the partner\'s personal space', function (Closure $space) {
        [$user, $partner] = actingAsPartnered();

        $response = $this->putJson('/api/v1/trips/'.newId(), [
            'space_id' => $space($partner)->id,
            'title' => 'Liburan ke Bali',
        ]);

        $response->assertNotFound()->assertJsonPath('code', 'not_found');
        $this->assertDatabaseEmpty('trips');
    })->with([
        'the partner\'s personal space' => [fn (User $partner) => $partner->personalSpace()],
        'a space of another couple' => [fn () => Space::factory()->create()],
    ]);

    it('returns 422 for invalid values', function (array $overrides, string $field, ?string $message) {
        $user = actingAsApiUser();

        $response = $this->putJson('/api/v1/trips/'.newId(), [
            'space_id' => $user->personalSpace()->id,
            'title' => 'Liburan ke Bali',
            ...$overrides,
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonPath('current', null)
            ->assertJsonValidationErrors([$field]);
        if ($message !== null) {
            $response->assertJsonPath("errors.{$field}.0", $message);
        }
        $this->assertDatabaseEmpty('trips');
    })->with([
        'no space' => [['space_id' => null], 'space_id', null],
        'no title' => [['title' => ''], 'title', null],
        'a title longer than 50 characters' => [['title' => str_repeat('a', 51)], 'title', null],
        'a destination longer than 100 characters' => [['destination' => str_repeat('a', 101)], 'destination', null],
        'notes longer than 5,000 characters' => [['notes' => str_repeat('a', 5001)], 'notes', null],
        'a start date that is not on the calendar' => [['start_date' => '2027-02-30'], 'start_date', null],
        'an end date without a start date' => [['end_date' => '2027-06-25'], 'end_date', 'Tanggal selesai butuh tanggal mulai.'],
        'an end date before the start date' => [['start_date' => '2027-06-20', 'end_date' => '2027-06-19'], 'end_date', 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.'],
    ]);

    it('returns 422 when the goal is in another space', function (Closure $goal) {
        [$user, $partner, $sharedSpace] = actingAsPartnered();

        $response = $this->putJson('/api/v1/trips/'.newId(), [
            'space_id' => $sharedSpace->id,
            'title' => 'Liburan ke Bali',
            'savings_goal_id' => $goal($user)->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.savings_goal_id.0', 'Target tabungan harus berada di ruang yang sama dengan perjalanannya.');
        $this->assertDatabaseEmpty('trips');
    })->with([
        'a goal in the user\'s personal space' => [fn (User $user) => SavingsGoal::factory()->for($user->personalSpace())->create()],
        'a goal of another couple' => [fn () => SavingsGoal::factory()->create()],
    ]);

    it('returns 401 when no token is sent', function () {
        $response = $this->putJson('/api/v1/trips/'.newId(), []);

        $response->assertUnauthorized();
    });
});

describe('update', function () {
    it('changes only the fields that were sent', function () {
        [$user, $partner, $space] = actingAsPartnered();
        $trip = Trip::factory()->for($space)->create(['title' => 'Liburan ke Bali', 'start_date' => '2027-06-20', 'end_date' => '2027-06-25']);

        $response = $this->patchJson("/api/v1/trips/{$trip->id}", ['end_date' => '2027-06-27']);

        $response->assertOk()
            ->assertJsonPath('data.end_date', '2027-06-27')
            ->assertJsonPath('data.start_date', '2027-06-20')
            ->assertJsonPath('data.title', 'Liburan ke Bali');
    });

    it('checks a new end date against the start date already stored', function () {
        $user = actingAsApiUser();
        $trip = Trip::factory()->for($user->personalSpace())->create(['start_date' => '2027-06-20', 'end_date' => '2027-06-25']);

        $response = $this->patchJson("/api/v1/trips/{$trip->id}", ['end_date' => '2027-06-19']);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.end_date.0', 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.')
            ->assertJsonPath('current.end_date', '2027-06-25');
    });

    it('links a goal, and unlinks it with null', function () {
        $user = actingAsApiUser();
        $trip = Trip::factory()->for($user->personalSpace())->create();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create();

        $linked = $this->patchJson("/api/v1/trips/{$trip->id}", ['savings_goal_id' => $goal->id]);
        $unlinked = $this->patchJson("/api/v1/trips/{$trip->id}", ['savings_goal_id' => null]);

        $linked->assertOk()->assertJsonPath('data.savings_goal_id', $goal->id);
        $unlinked->assertOk()->assertJsonPath('data.savings_goal_id', null);
    });

    it('keeps the trip in the space it was created in', function () {
        [$user, $partner, $space] = actingAsPartnered();
        $trip = Trip::factory()->for($space)->create();

        $response = $this->patchJson("/api/v1/trips/{$trip->id}", ['space_id' => $user->personalSpace()->id, 'title' => 'Bulan madu']);

        $response->assertOk()->assertJsonPath('data.space_id', $space->id);
    });

    it('returns 404 with the not_found code for a trip the user cannot reach', function (Closure $id) {
        [$user, $partner] = actingAsPartnered();

        $response = $this->patchJson('/api/v1/trips/'.$id($partner), ['title' => 'Liburan ke Bali']);

        $response->assertNotFound()->assertJsonPath('code', 'not_found')->assertJsonPath('current', null);
    })->with([
        'a trip in the partner\'s personal space' => [fn (User $partner) => Trip::factory()->for($partner->personalSpace())->create()->id],
        'a trip in the space of another couple' => [fn () => Trip::factory()->create()->id],
        'an ID that was never created' => [fn () => newId()],
    ]);

    it('returns 410 with the resource_deleted code for a deleted trip', function () {
        $user = actingAsApiUser();
        $trip = Trip::factory()->for($user->personalSpace())->create();
        $trip->delete();

        $response = $this->patchJson("/api/v1/trips/{$trip->id}", ['title' => 'Liburan ke Bali']);

        $response->assertGone()->assertJsonPath('code', 'resource_deleted');
    });

    it('returns 401 when no token is sent', function () {
        $response = $this->patchJson('/api/v1/trips/'.newId(), ['title' => 'Liburan ke Bali']);

        $response->assertUnauthorized();
    });
});

describe('destroy', function () {
    it('soft deletes the trip with its checklist and the checklist\'s tasks, and bumps their updated_at', function () {
        $this->travelTo('2026-10-22 10:00:00');
        $user = actingAsApiUser();
        $trip = Trip::factory()->for($user->personalSpace())->create();
        $checklist = TaskList::factory()->for($user->personalSpace())->create(['trip_id' => $trip->id]);
        $task = Task::factory()->for($checklist)->create();
        $this->travelTo('2026-10-22 11:00:00.250000');

        $response = $this->deleteJson("/api/v1/trips/{$trip->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted($trip);
        $this->assertSoftDeleted($checklist);
        $this->assertSoftDeleted($task);
        expect($checklist->fresh()->updated_at->toJSON())->toBe('2026-10-22T11:00:00.250000Z')
            ->and($task->fresh()->updated_at->toJSON())->toBe('2026-10-22T11:00:00.250000Z');
    });

    it('leaves the ordinary lists of the space alone', function () {
        $user = actingAsApiUser();
        $trip = Trip::factory()->for($user->personalSpace())->create();

        $this->deleteJson("/api/v1/trips/{$trip->id}");

        $this->assertNotSoftDeleted($user->personalSpace()->taskLists()->sole());
    });

    it('returns 204 again when the delete is repeated', function () {
        $user = actingAsApiUser();
        $trip = Trip::factory()->for($user->personalSpace())->create();
        $trip->delete();

        $response = $this->deleteJson("/api/v1/trips/{$trip->id}");

        $response->assertNoContent();
    });

    it('returns 404 for a trip in the space of another couple', function () {
        actingAsApiUser();
        $trip = Trip::factory()->create();

        $response = $this->deleteJson("/api/v1/trips/{$trip->id}");

        $response->assertNotFound()->assertJsonPath('code', 'not_found');
        $this->assertNotSoftDeleted($trip);
    });

    it('returns 401 when no token is sent', function () {
        $response = $this->deleteJson('/api/v1/trips/'.newId());

        $response->assertUnauthorized();
    });
});
