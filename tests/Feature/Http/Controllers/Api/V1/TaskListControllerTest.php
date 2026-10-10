<?php

use App\Models\Space;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\User;

describe('store', function () {
    it('creates the list with the ID from the phone and returns 201', function () {
        $this->travelTo('2026-10-08 10:30:00.123456');
        $user = actingAsApiUser();
        $space = $user->personalSpace();
        $id = newId();

        $response = $this->putJson("/api/v1/lists/{$id}", [
            'space_id' => $space->id,
            'name' => 'Belanja',
            'emoji' => '🛒',
            'color' => 'toska',
            'position' => 2048,
        ]);

        $response->assertCreated()->assertExactJson([
            'data' => [
                'id' => $id,
                'space_id' => $space->id,
                'name' => 'Belanja',
                'emoji' => '🛒',
                'color' => 'toska',
                'position' => 2048,
                'archived_at' => null,
                'created_by' => $user->id,
                'created_at' => '2026-10-08T10:30:00.123456Z',
                'updated_at' => '2026-10-08T10:30:00.123456Z',
                'deleted_at' => null,
            ],
        ]);
        $this->assertDatabaseHas('task_lists', ['id' => $id, 'space_id' => $space->id, 'name' => 'Belanja']);
    });

    it('uses mawar and no emoji when they are left out, and keeps a fractional position', function () {
        $user = actingAsApiUser();

        $response = $this->putJson('/api/v1/lists/'.newId(), [
            'space_id' => $user->personalSpace()->id,
            'name' => 'Belanja',
            'position' => 1536.5,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.color', 'mawar')
            ->assertJsonPath('data.emoji', null)
            ->assertJsonPath('data.position', 1536.5);
    });

    it('accepts an emoji made of several code points', function () {
        $user = actingAsApiUser();

        $response = $this->putJson('/api/v1/lists/'.newId(), [
            'space_id' => $user->personalSpace()->id,
            'name' => 'Keluarga',
            'emoji' => '👨‍👩‍👧‍👦',
            'position' => 2048,
        ]);

        $response->assertCreated()->assertJsonPath('data.emoji', '👨‍👩‍👧‍👦');
    });

    it('returns 200 with the stored list and changes nothing when the PUT is repeated', function () {
        $user = actingAsApiUser();
        $list = TaskList::factory()->for($user->personalSpace())->create(['name' => 'Belanja']);

        $response = $this->putJson("/api/v1/lists/{$list->id}", [
            'space_id' => $list->space_id,
            'name' => 'Nama dari kiriman ulang',
            'position' => 9999,
        ]);

        $response->assertOk()->assertJsonPath('data.name', 'Belanja');
        $this->assertDatabaseHas('task_lists', ['id' => $list->id, 'name' => 'Belanja']);
    });

    it('returns 410 with the resource_deleted code when the ID belongs to a deleted list', function () {
        $user = actingAsApiUser();
        $list = TaskList::factory()->for($user->personalSpace())->create();
        $list->delete();

        $response = $this->putJson("/api/v1/lists/{$list->id}", [
            'space_id' => $list->space_id,
            'name' => 'Belanja',
            'position' => 2048,
        ]);

        $response->assertGone()
            ->assertJsonPath('code', 'resource_deleted')
            ->assertJsonPath('current.id', $list->id);
        expect($response->json('current.deleted_at'))->not->toBeNull();
    });

    it('returns 409 with the id_conflict code when the ID is used in another space', function (Closure $existingSpace) {
        $user = actingAsApiUser();
        $list = TaskList::factory()->for($existingSpace($user))->create();

        $response = $this->putJson("/api/v1/lists/{$list->id}", [
            'space_id' => $user->personalSpace()->id,
            'name' => 'Belanja',
            'position' => 2048,
        ]);

        $response->assertConflict()->assertJsonPath('code', 'id_conflict');
        $this->assertDatabaseCount('task_lists', 2);
    })->with([
        'the space shared with the partner' => [fn (User $user) => sharedSpaceBetween($user, User::factory()->create())],
        'a space of another couple' => [fn () => Space::factory()->create()],
    ]);

    it('does not reveal a list of another couple through current', function () {
        $user = actingAsApiUser();
        $list = TaskList::factory()->create();

        $response = $this->putJson("/api/v1/lists/{$list->id}", [
            'space_id' => $user->personalSpace()->id,
            'name' => 'Belanja',
            'position' => 2048,
        ]);

        $response->assertConflict()->assertJsonPath('current', null);
    });

    it('returns 404 when the space is not one of the user\'s', function () {
        actingAsApiUser();
        $otherSpace = Space::factory()->create();
        $id = newId();

        $response = $this->putJson("/api/v1/lists/{$id}", [
            'space_id' => $otherSpace->id,
            'name' => 'Belanja',
            'position' => 2048,
        ]);

        $response->assertNotFound()->assertJsonPath('code', 'not_found');
        $this->assertDatabaseMissing('task_lists', ['id' => $id]);
    });

    it('returns 422 for invalid values', function (array $overrides, string $field) {
        $user = actingAsApiUser();
        $id = newId();

        $response = $this->putJson("/api/v1/lists/{$id}", [
            'space_id' => $user->personalSpace()->id,
            'name' => 'Belanja',
            'position' => 2048,
            ...$overrides,
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonPath('current', null)
            ->assertJsonValidationErrors([$field]);
        $this->assertDatabaseMissing('task_lists', ['id' => $id]);
    })->with([
        'no space' => [['space_id' => null], 'space_id'],
        'a space ID in uppercase' => [['space_id' => '01M4DGDT40TE98HY85V3FE2T18'], 'space_id'],
        'no name' => [['name' => ''], 'name'],
        'a name longer than 50 characters' => [['name' => str_repeat('a', 51)], 'name'],
        'an emoji longer than 16 code points' => [['emoji' => str_repeat('a', 17)], 'emoji'],
        'a color outside the palette' => [['color' => 'hijau'], 'color'],
        'no position' => [['position' => null], 'position'],
        'a position that is not a number' => [['position' => 'atas'], 'position'],
    ]);

    it('returns 422 on the id field when the ULID in the URL is in uppercase', function () {
        $user = actingAsApiUser();

        $response = $this->putJson('/api/v1/lists/01M4DH0420ZQEXWXS1N4YW5JN2', [
            'space_id' => $user->personalSpace()->id,
            'name' => 'Belanja',
            'position' => 2048,
        ]);

        $response->assertUnprocessable()->assertJsonPath('errors.id.0', 'ID harus berupa ULID huruf kecil.');
        $this->assertDatabaseCount('task_lists', 1);
    });

    it('returns 401 when no token is sent', function () {
        $response = $this->putJson('/api/v1/lists/'.newId(), []);

        $response->assertUnauthorized();
    });
});

describe('update', function () {
    it('changes only the fields that were sent', function () {
        $user = actingAsApiUser();
        $list = TaskList::factory()->for($user->personalSpace())->create(['name' => 'Belanja', 'color' => 'toska']);

        $response = $this->patchJson("/api/v1/lists/{$list->id}", ['name' => 'Belanja bulanan', 'position' => 512]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Belanja bulanan')
            ->assertJsonPath('data.position', 512)
            ->assertJsonPath('data.color', 'toska');
    });

    it('lets the partner change a list in the shared space', function () {
        $user = actingAsApiUser();
        $partner = User::factory()->create();
        $list = TaskList::factory()->for(sharedSpaceBetween($partner, $user))->create();

        $response = $this->patchJson("/api/v1/lists/{$list->id}", ['name' => 'Diubah pasangan']);

        $response->assertOk()->assertJsonPath('data.name', 'Diubah pasangan');
    });

    it('clears the emoji when null is sent', function () {
        $user = actingAsApiUser();
        $list = TaskList::factory()->for($user->personalSpace())->create(['emoji' => '🛒']);

        $response = $this->patchJson("/api/v1/lists/{$list->id}", ['emoji' => null]);

        $response->assertOk()->assertJsonPath('data.emoji', null);
    });

    it('archives the list at the time the phone sent, converted to UTC', function () {
        $this->travelTo('2026-10-08 12:00:00');
        $user = actingAsApiUser();
        $list = TaskList::factory()->for($user->personalSpace())->create();

        $response = $this->patchJson("/api/v1/lists/{$list->id}", ['archived_at' => '2026-10-08T17:30:00+07:00']);

        $response->assertOk()->assertJsonPath('data.archived_at', '2026-10-08T10:30:00.000000Z');
    });

    it('pulls an archive time ahead of the server clock back to now', function () {
        $this->travelTo('2026-10-08 12:00:00');
        $user = actingAsApiUser();
        $list = TaskList::factory()->for($user->personalSpace())->create();

        $response = $this->patchJson("/api/v1/lists/{$list->id}", ['archived_at' => '2026-10-09T00:00:00Z']);

        $response->assertOk()->assertJsonPath('data.archived_at', '2026-10-08T12:00:00.000000Z');
    });

    it('takes the list out of the archive when null is sent', function () {
        $user = actingAsApiUser();
        $list = TaskList::factory()->for($user->personalSpace())->archived()->create();

        $response = $this->patchJson("/api/v1/lists/{$list->id}", ['archived_at' => null]);

        $response->assertOk()->assertJsonPath('data.archived_at', null);
    });

    it('returns 409 with the last_list code when archiving the only active list of the space', function () {
        $user = actingAsApiUser();
        $list = $user->personalSpace()->taskLists()->sole();
        TaskList::factory()->for($user->personalSpace())->archived()->create();

        $response = $this->patchJson("/api/v1/lists/{$list->id}", ['archived_at' => '2026-10-08T10:30:00Z']);

        $response->assertConflict()
            ->assertJsonPath('code', 'last_list')
            ->assertJsonPath('message', 'Setiap ruang harus punya minimal satu daftar aktif.')
            ->assertJsonPath('current.id', $list->id)
            ->assertJsonPath('current.archived_at', null);
        $this->assertDatabaseHas('task_lists', ['id' => $list->id, 'archived_at' => null]);
    });

    it('ignores the space and unknown fields', function () {
        $user = actingAsApiUser();
        $list = TaskList::factory()->for($user->personalSpace())->create();
        $otherSpace = sharedSpaceBetween($user, User::factory()->create());

        $response = $this->patchJson("/api/v1/lists/{$list->id}", [
            'space_id' => $otherSpace->id,
            'created_by' => newId(),
            'ukuran' => 'besar',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.space_id', $list->space_id)
            ->assertJsonPath('data.created_by', $list->created_by);
    });

    it('returns 404 with the not_found code for a list the user cannot reach', function (Closure $id) {
        actingAsApiUser();

        $response = $this->patchJson('/api/v1/lists/'.$id(), ['name' => 'Belanja']);

        $response->assertNotFound()->assertExactJson([
            'message' => 'Data tidak ditemukan.',
            'code' => 'not_found',
            'current' => null,
        ]);
    })->with([
        'a list in the space of another couple' => [fn () => TaskList::factory()->create()->id],
        'an ID that was never created' => [fn () => newId()],
    ]);

    it('returns 410 with the resource_deleted code for a deleted list', function () {
        $user = actingAsApiUser();
        $list = TaskList::factory()->for($user->personalSpace())->create();
        $list->delete();

        $response = $this->patchJson("/api/v1/lists/{$list->id}", ['name' => 'Belanja']);

        $response->assertGone()->assertJsonPath('code', 'resource_deleted');
    });

    it('returns 422 with the stored list as current for invalid values', function (array $payload, string $field) {
        $user = actingAsApiUser();
        $list = TaskList::factory()->for($user->personalSpace())->create(['name' => 'Belanja']);

        $response = $this->patchJson("/api/v1/lists/{$list->id}", $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([$field])
            ->assertJsonPath('current.name', 'Belanja');
    })->with([
        'an empty name' => [['name' => ''], 'name'],
        'a color outside the palette' => [['color' => 'hijau'], 'color'],
        'a position that is not a number' => [['position' => 'atas'], 'position'],
        'an archive time without an offset' => [['archived_at' => '2026-10-08T10:30:00'], 'archived_at'],
    ]);

    it('returns 401 when no token is sent', function () {
        $response = $this->patchJson('/api/v1/lists/'.newId(), ['name' => 'Belanja']);

        $response->assertUnauthorized();
    });
});

describe('destroy', function () {
    it('soft deletes the list with its tasks and bumps their updated_at', function () {
        $this->travelTo('2026-10-08 10:00:00');
        $user = actingAsApiUser();
        $list = TaskList::factory()->for($user->personalSpace())->create();
        $task = Task::factory()->for($list)->create();
        $this->travelTo('2026-10-08 11:00:00.250000');

        $response = $this->deleteJson("/api/v1/lists/{$list->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted($list);
        $this->assertSoftDeleted($task);
        expect($task->fresh()->updated_at->toJSON())->toBe('2026-10-08T11:00:00.250000Z')
            ->and($list->fresh()->updated_at->toJSON())->toBe('2026-10-08T11:00:00.250000Z');
    });

    it('leaves the tasks of other lists alone', function () {
        $user = actingAsApiUser();
        $list = TaskList::factory()->for($user->personalSpace())->create();
        $otherTask = Task::factory()->for(TaskList::factory()->for($user->personalSpace()))->create();

        $this->deleteJson("/api/v1/lists/{$list->id}");

        $this->assertNotSoftDeleted($otherTask);
    });

    it('returns 204 again when the delete is repeated', function () {
        $user = actingAsApiUser();
        $list = TaskList::factory()->for($user->personalSpace())->create();
        $list->delete();

        $response = $this->deleteJson("/api/v1/lists/{$list->id}");

        $response->assertNoContent();
    });

    it('returns 409 with the last_list code when deleting the only active list of the space', function () {
        $user = actingAsApiUser();
        $list = $user->personalSpace()->taskLists()->sole();

        $response = $this->deleteJson("/api/v1/lists/{$list->id}");

        $response->assertConflict()->assertJsonPath('code', 'last_list');
        $this->assertNotSoftDeleted($list);
    });

    it('deletes an archived list even when only one active list remains', function () {
        $user = actingAsApiUser();
        $archivedList = TaskList::factory()->for($user->personalSpace())->archived()->create();

        $response = $this->deleteJson("/api/v1/lists/{$archivedList->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted($archivedList);
    });

    it('returns 404 for a list in the space of another couple', function () {
        actingAsApiUser();
        $list = TaskList::factory()->create();

        $response = $this->deleteJson("/api/v1/lists/{$list->id}");

        $response->assertNotFound()->assertJsonPath('code', 'not_found');
        $this->assertNotSoftDeleted($list);
    });

    it('returns 401 when no token is sent', function () {
        $response = $this->deleteJson('/api/v1/lists/'.newId());

        $response->assertUnauthorized();
    });
});
