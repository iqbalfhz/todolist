<?php

use App\Models\Space;
use App\Models\SpecialDate;
use App\Models\User;

describe('store', function () {
    it('creates the date with the ID from the phone and returns 201', function () {
        $this->travelTo('2026-10-22 09:00:00.123456');
        [$user, $partner, $space] = actingAsPartnered();
        $id = newId();

        $response = $this->putJson("/api/v1/special-dates/{$id}", [
            'space_id' => $space->id,
            'type' => 'anniversary',
            'title' => 'Hari jadi kita',
            'date' => '2023-10-29',
        ]);

        $response->assertCreated()->assertExactJson([
            'data' => [
                'id' => $id,
                'space_id' => $space->id,
                'type' => 'anniversary',
                'title' => 'Hari jadi kita',
                'date' => '2023-10-29',
                'created_by' => $user->id,
                'created_at' => '2026-10-22T09:00:00.123456Z',
                'updated_at' => '2026-10-22T09:00:00.123456Z',
                'deleted_at' => null,
            ],
        ]);
    });

    it('accepts a date without a year, including 29 February', function (string $date) {
        $user = actingAsApiUser();

        $response = $this->putJson('/api/v1/special-dates/'.newId(), [
            'space_id' => $user->personalSpace()->id,
            'type' => 'birthday',
            'title' => 'Ulang tahun Ibu',
            'date' => $date,
        ]);

        $response->assertCreated()->assertJsonPath('data.date', $date);
    })->with(['--03-02', '--02-29']);

    it('returns 200 with the stored date and changes nothing when the PUT is repeated', function () {
        $user = actingAsApiUser();
        $specialDate = SpecialDate::factory()->for($user->personalSpace())->create(['title' => 'Hari jadi kita']);

        $response = $this->putJson("/api/v1/special-dates/{$specialDate->id}", [
            'space_id' => $specialDate->space_id,
            'type' => 'other',
            'title' => 'Judul dari kiriman ulang',
            'date' => '2024-01-01',
        ]);

        $response->assertOk()->assertJsonPath('data.title', 'Hari jadi kita');
        $this->assertDatabaseHas('special_dates', ['id' => $specialDate->id, 'title' => 'Hari jadi kita', 'type' => 'anniversary']);
    });

    it('returns 410 with the resource_deleted code when the ID belongs to a deleted date', function () {
        $user = actingAsApiUser();
        $specialDate = SpecialDate::factory()->for($user->personalSpace())->create();
        $specialDate->delete();

        $response = $this->putJson("/api/v1/special-dates/{$specialDate->id}", [
            'space_id' => $specialDate->space_id,
            'type' => 'anniversary',
            'title' => 'Hari jadi kita',
            'date' => '2023-10-29',
        ]);

        $response->assertGone()->assertJsonPath('code', 'resource_deleted');
    });

    it('returns 409 with the id_conflict code when the ID is used in another space', function (Closure $existingSpace) {
        $user = actingAsApiUser();
        $specialDate = SpecialDate::factory()->for($existingSpace($user))->create();

        $response = $this->putJson("/api/v1/special-dates/{$specialDate->id}", [
            'space_id' => $user->personalSpace()->id,
            'type' => 'anniversary',
            'title' => 'Hari jadi kita',
            'date' => '2023-10-29',
        ]);

        $response->assertConflict()->assertJsonPath('code', 'id_conflict');
        $this->assertDatabaseCount('special_dates', 1);
    })->with([
        'the space shared with the partner' => [fn (User $user) => sharedSpaceBetween($user, User::factory()->create())],
        'a space of another couple' => [fn () => Space::factory()->create()],
    ]);

    it('returns 404 when the space is not one of the user\'s, including the partner\'s personal space', function (Closure $space) {
        [$user, $partner] = actingAsPartnered();

        $response = $this->putJson('/api/v1/special-dates/'.newId(), [
            'space_id' => $space($partner)->id,
            'type' => 'anniversary',
            'title' => 'Hari jadi kita',
            'date' => '2023-10-29',
        ]);

        $response->assertNotFound()->assertJsonPath('code', 'not_found');
        $this->assertDatabaseEmpty('special_dates');
    })->with([
        'the partner\'s personal space' => [fn (User $partner) => $partner->personalSpace()],
        'a space of another couple' => [fn () => Space::factory()->create()],
    ]);

    it('returns 422 for invalid values', function (array $overrides, string $field) {
        $user = actingAsApiUser();

        $response = $this->putJson('/api/v1/special-dates/'.newId(), [
            'space_id' => $user->personalSpace()->id,
            'type' => 'anniversary',
            'title' => 'Hari jadi kita',
            'date' => '2023-10-29',
            ...$overrides,
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonPath('current', null)
            ->assertJsonValidationErrors([$field]);
        $this->assertDatabaseEmpty('special_dates');
    })->with([
        'no space' => [['space_id' => null], 'space_id'],
        'a type outside the list' => [['type' => 'holiday'], 'type'],
        'no title' => [['title' => ''], 'title'],
        'a title longer than 50 characters' => [['title' => str_repeat('a', 51)], 'title'],
        'no date' => [['date' => null], 'date'],
        'a date that is not on the calendar' => [['date' => '--02-30'], 'date'],
        'a date in another format' => [['date' => '29/10/2023'], 'date'],
    ]);

    it('returns 401 when no token is sent', function () {
        $response = $this->putJson('/api/v1/special-dates/'.newId(), []);

        $response->assertUnauthorized();
    });
});

describe('update', function () {
    it('changes only the fields that were sent', function () {
        [$user, $partner, $space] = actingAsPartnered();
        $specialDate = SpecialDate::factory()->for($space)->create(['title' => 'Hari jadi kita', 'date' => '2023-10-29']);

        $response = $this->patchJson("/api/v1/special-dates/{$specialDate->id}", ['date' => '2023-10-30']);

        $response->assertOk()
            ->assertJsonPath('data.date', '2023-10-30')
            ->assertJsonPath('data.title', 'Hari jadi kita')
            ->assertJsonPath('data.type', 'anniversary');
    });

    it('keeps the date in the space it was created in', function () {
        [$user, $partner, $space] = actingAsPartnered();
        $specialDate = SpecialDate::factory()->for($space)->create();

        $response = $this->patchJson("/api/v1/special-dates/{$specialDate->id}", ['space_id' => $user->personalSpace()->id, 'title' => 'Hari jadi']);

        $response->assertOk()->assertJsonPath('data.space_id', $space->id);
    });

    it('returns 404 with the not_found code for a date the user cannot reach', function (Closure $id) {
        [$user, $partner] = actingAsPartnered();

        $response = $this->patchJson('/api/v1/special-dates/'.$id($partner), ['title' => 'Hari jadi kita']);

        $response->assertNotFound()->assertJsonPath('code', 'not_found')->assertJsonPath('current', null);
    })->with([
        'a date in the partner\'s personal space' => [fn (User $partner) => SpecialDate::factory()->for($partner->personalSpace())->create()->id],
        'a date in the space of another couple' => [fn () => SpecialDate::factory()->create()->id],
        'an ID that was never created' => [fn () => newId()],
    ]);

    it('returns 410 with the resource_deleted code for a deleted date', function () {
        $user = actingAsApiUser();
        $specialDate = SpecialDate::factory()->for($user->personalSpace())->create();
        $specialDate->delete();

        $response = $this->patchJson("/api/v1/special-dates/{$specialDate->id}", ['title' => 'Hari jadi kita']);

        $response->assertGone()->assertJsonPath('code', 'resource_deleted');
    });

    it('returns 422 with the stored date as current for invalid values', function (array $payload, string $field) {
        $user = actingAsApiUser();
        $specialDate = SpecialDate::factory()->for($user->personalSpace())->create(['title' => 'Hari jadi kita']);

        $response = $this->patchJson("/api/v1/special-dates/{$specialDate->id}", $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([$field])
            ->assertJsonPath('current.title', 'Hari jadi kita');
    })->with([
        'an empty title' => [['title' => ''], 'title'],
        'a type outside the list' => [['type' => 'holiday'], 'type'],
        'a date that is not on the calendar' => [['date' => '2023-02-29'], 'date'],
    ]);

    it('returns 401 when no token is sent', function () {
        $response = $this->patchJson('/api/v1/special-dates/'.newId(), ['title' => 'Hari jadi kita']);

        $response->assertUnauthorized();
    });
});

describe('destroy', function () {
    it('soft deletes the date', function () {
        $user = actingAsApiUser();
        $specialDate = SpecialDate::factory()->for($user->personalSpace())->create();

        $response = $this->deleteJson("/api/v1/special-dates/{$specialDate->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted($specialDate);
    });

    it('returns 204 again when the delete is repeated', function () {
        $user = actingAsApiUser();
        $specialDate = SpecialDate::factory()->for($user->personalSpace())->create();
        $specialDate->delete();

        $response = $this->deleteJson("/api/v1/special-dates/{$specialDate->id}");

        $response->assertNoContent();
    });

    it('returns 404 for a date in the space of another couple', function () {
        actingAsApiUser();
        $specialDate = SpecialDate::factory()->create();

        $response = $this->deleteJson("/api/v1/special-dates/{$specialDate->id}");

        $response->assertNotFound()->assertJsonPath('code', 'not_found');
        $this->assertNotSoftDeleted($specialDate);
    });

    it('returns 401 when no token is sent', function () {
        $response = $this->deleteJson('/api/v1/special-dates/'.newId());

        $response->assertUnauthorized();
    });
});
