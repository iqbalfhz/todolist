<?php

use App\Jobs\DeleteStoredFiles;
use App\Jobs\NotifyMemoryCreated;
use App\Models\Memory;
use App\Models\MemoryPhoto;
use App\Models\Space;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

describe('store', function () {
    it('creates the memory with the ID from the phone and returns 201', function () {
        $this->travelTo('2026-10-23 10:00:00.123456');
        [$user, $partner, $space] = actingAsPartnered();
        $trip = Trip::factory()->for($space)->create();
        $id = newId();

        $response = $this->putJson("/api/v1/memories/{$id}", [
            'space_id' => $space->id,
            'trip_id' => $trip->id,
            'title' => 'Liburan ke Bali',
            'place_name' => 'Ubud, Bali',
            'latitude' => -8.50685349,
            'longitude' => 115.26247751,
            'visited_on' => '2027-06-20',
            'story' => 'Hari pertama hujan, tapi sawahnya tetap hijau.',
        ]);

        $response->assertCreated()->assertExactJson([
            'data' => [
                'id' => $id,
                'space_id' => $space->id,
                'trip_id' => $trip->id,
                'title' => 'Liburan ke Bali',
                'place_name' => 'Ubud, Bali',
                'latitude' => -8.506853,
                'longitude' => 115.262478,
                'visited_on' => '2027-06-20',
                'story' => 'Hari pertama hujan, tapi sawahnya tetap hijau.',
                'created_by' => $user->id,
                'created_at' => '2026-10-23T10:00:00.123456Z',
                'updated_at' => '2026-10-23T10:00:00.123456Z',
                'deleted_at' => null,
            ],
        ]);
    });

    it('accepts a memory with only a title and a date', function () {
        $user = actingAsApiUser();

        $response = $this->putJson('/api/v1/memories/'.newId(), [
            'space_id' => $user->personalSpace()->id,
            'title' => 'Makan malam pertama',
            'visited_on' => '2023-10-29',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.place_name', null)
            ->assertJsonPath('data.story', null)
            ->assertJsonPath('data.trip_id', null);
    });

    it('stores no link when the trip was already deleted, and keeps the rest of the memory', function () {
        $user = actingAsApiUser();
        $trip = Trip::factory()->for($user->personalSpace())->create();
        $trip->delete();

        $response = $this->putJson('/api/v1/memories/'.newId(), [
            'space_id' => $user->personalSpace()->id,
            'trip_id' => $trip->id,
            'title' => 'Liburan ke Bali',
            'visited_on' => '2027-06-20',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Liburan ke Bali')
            ->assertJsonPath('data.trip_id', null);
    });

    it('lets one trip have several memories', function () {
        $user = actingAsApiUser();
        $trip = Trip::factory()->for($user->personalSpace())->create();
        Memory::factory()->for($user->personalSpace())->create(['trip_id' => $trip->id]);

        $response = $this->putJson('/api/v1/memories/'.newId(), [
            'space_id' => $user->personalSpace()->id,
            'trip_id' => $trip->id,
            'title' => 'Hari kedua di Bali',
            'visited_on' => '2027-06-21',
        ]);

        $response->assertCreated()->assertJsonPath('data.trip_id', $trip->id);
    });

    it('queues the push for the partner to go out 2 minutes later', function () {
        Queue::fake([NotifyMemoryCreated::class]);
        $this->travelTo('2026-10-23 10:00:00');
        [$user, $partner, $space] = actingAsPartnered();
        $id = newId();

        $this->putJson("/api/v1/memories/{$id}", [
            'space_id' => $space->id,
            'title' => 'Liburan ke Bali',
            'visited_on' => '2027-06-20',
        ]);

        Queue::assertPushed(NotifyMemoryCreated::class, fn (NotifyMemoryCreated $job) => $job->memoryId === $id
            && $job->actorId === $user->id
            && $job->delay->eq(now()->addMinutes(2)));
    });

    it('queues no push for a memory in the personal space', function () {
        Queue::fake([NotifyMemoryCreated::class]);
        [$user] = actingAsPartnered();

        $this->putJson('/api/v1/memories/'.newId(), [
            'space_id' => $user->personalSpace()->id,
            'title' => 'Catatan sendiri',
            'visited_on' => '2027-06-20',
        ]);

        Queue::assertNotPushed(NotifyMemoryCreated::class);
    });

    it('returns 200 with the stored memory and changes nothing when the PUT is repeated', function () {
        Queue::fake([NotifyMemoryCreated::class]);
        [$user, $partner, $space] = actingAsPartnered();
        $memory = Memory::factory()->for($space)->create(['title' => 'Liburan ke Bali']);

        $response = $this->putJson("/api/v1/memories/{$memory->id}", [
            'space_id' => $memory->space_id,
            'title' => 'Judul dari kiriman ulang',
            'visited_on' => '2027-06-20',
        ]);

        $response->assertOk()->assertJsonPath('data.title', 'Liburan ke Bali');
        $this->assertDatabaseHas('memories', ['id' => $memory->id, 'title' => 'Liburan ke Bali']);
        Queue::assertNotPushed(NotifyMemoryCreated::class);
    });

    it('returns 410 with the resource_deleted code when the ID belongs to a deleted memory', function () {
        $user = actingAsApiUser();
        $memory = Memory::factory()->for($user->personalSpace())->create();
        $memory->delete();

        $response = $this->putJson("/api/v1/memories/{$memory->id}", [
            'space_id' => $memory->space_id,
            'title' => 'Liburan ke Bali',
            'visited_on' => '2027-06-20',
        ]);

        $response->assertGone()->assertJsonPath('code', 'resource_deleted');
    });

    it('returns 409 with the id_conflict code when the ID is used in another space', function (Closure $existingSpace) {
        $user = actingAsApiUser();
        $memory = Memory::factory()->for($existingSpace($user))->create();

        $response = $this->putJson("/api/v1/memories/{$memory->id}", [
            'space_id' => $user->personalSpace()->id,
            'title' => 'Liburan ke Bali',
            'visited_on' => '2027-06-20',
        ]);

        $response->assertConflict()->assertJsonPath('code', 'id_conflict');
        $this->assertDatabaseCount('memories', 1);
    })->with([
        'the space shared with the partner' => [fn (User $user) => sharedSpaceBetween($user, User::factory()->create())],
        'a space of another couple' => [fn () => Space::factory()->create()],
    ]);

    it('returns 404 when the space is not one of the user\'s, including the partner\'s personal space', function (Closure $space) {
        [$user, $partner] = actingAsPartnered();

        $response = $this->putJson('/api/v1/memories/'.newId(), [
            'space_id' => $space($partner)->id,
            'title' => 'Liburan ke Bali',
            'visited_on' => '2027-06-20',
        ]);

        $response->assertNotFound()->assertJsonPath('code', 'not_found');
        $this->assertDatabaseEmpty('memories');
    })->with([
        'the partner\'s personal space' => [fn (User $partner) => $partner->personalSpace()],
        'a space of another couple' => [fn () => Space::factory()->create()],
    ]);

    it('returns 422 for invalid values', function (array $overrides, string $field) {
        $user = actingAsApiUser();

        $response = $this->putJson('/api/v1/memories/'.newId(), [
            'space_id' => $user->personalSpace()->id,
            'title' => 'Liburan ke Bali',
            'visited_on' => '2027-06-20',
            ...$overrides,
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonPath('current', null)
            ->assertJsonValidationErrors([$field]);
        $this->assertDatabaseEmpty('memories');
    })->with([
        'no space' => [['space_id' => null], 'space_id'],
        'no title' => [['title' => ''], 'title'],
        'a title longer than 50 characters' => [['title' => str_repeat('a', 51)], 'title'],
        'a place name longer than 100 characters' => [['place_name' => str_repeat('a', 101)], 'place_name'],
        'no date' => [['visited_on' => null], 'visited_on'],
        'a date that is not on the calendar' => [['visited_on' => '2027-02-30'], 'visited_on'],
        'a story longer than 10,000 characters' => [['story' => str_repeat('a', 10001)], 'story'],
        'an uppercase trip ID' => [['trip_id' => '01M4DP5R20VBX9K3WS7TY6HN2Q'], 'trip_id'],
        'a latitude without a longitude' => [['latitude' => -8.5], 'longitude'],
        'a longitude without a latitude' => [['longitude' => 115.2], 'latitude'],
        'a latitude with an empty longitude' => [['latitude' => -8.5, 'longitude' => null], 'longitude'],
        'a longitude with an empty latitude' => [['latitude' => null, 'longitude' => 115.2], 'latitude'],
        'a latitude beyond the pole' => [['latitude' => 90.000001, 'longitude' => 115.2], 'latitude'],
        'a longitude beyond 180' => [['latitude' => -8.5, 'longitude' => -180.5], 'longitude'],
        'a latitude that is not a number' => [['latitude' => 'utara', 'longitude' => 115.2], 'latitude'],
    ]);

    it('accepts a story of 10,000 characters counted as code points', function () {
        $user = actingAsApiUser();

        $response = $this->putJson('/api/v1/memories/'.newId(), [
            'space_id' => $user->personalSpace()->id,
            'title' => 'Liburan ke Bali',
            'visited_on' => '2027-06-20',
            'story' => str_repeat('é', 10000),
        ]);

        $response->assertCreated();
    });

    it('returns 422 when the trip is in another space', function (Closure $trip) {
        [$user, $partner, $sharedSpace] = actingAsPartnered();

        $response = $this->putJson('/api/v1/memories/'.newId(), [
            'space_id' => $sharedSpace->id,
            'trip_id' => $trip($user)->id,
            'title' => 'Liburan ke Bali',
            'visited_on' => '2027-06-20',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.trip_id.0', 'Perjalanan harus berada di ruang yang sama dengan kenangannya.');
        $this->assertDatabaseEmpty('memories');
    })->with([
        'a trip in the user\'s personal space' => [fn (User $user) => Trip::factory()->for($user->personalSpace())->create()],
        'a trip of another couple' => [fn () => Trip::factory()->create()],
    ]);

    it('returns 401 when no token is sent', function () {
        $response = $this->putJson('/api/v1/memories/'.newId(), []);

        $response->assertUnauthorized();
    });
});

describe('update', function () {
    it('changes only the fields that were sent', function () {
        [$user, $partner, $space] = actingAsPartnered();
        $memory = Memory::factory()->for($space)->create(['title' => 'Liburan ke Bali', 'place_name' => 'Ubud, Bali']);

        $response = $this->patchJson("/api/v1/memories/{$memory->id}", ['story' => 'Sawahnya hijau sekali.']);

        $response->assertOk()
            ->assertJsonPath('data.story', 'Sawahnya hijau sekali.')
            ->assertJsonPath('data.title', 'Liburan ke Bali')
            ->assertJsonPath('data.place_name', 'Ubud, Bali');
    });

    it('sets the pin, and removes it with two nulls', function () {
        $user = actingAsApiUser();
        $memory = Memory::factory()->for($user->personalSpace())->create();

        $placed = $this->patchJson("/api/v1/memories/{$memory->id}", ['latitude' => -8.506853, 'longitude' => 115.262478]);
        $removed = $this->patchJson("/api/v1/memories/{$memory->id}", ['latitude' => null, 'longitude' => null]);

        $placed->assertOk()->assertJsonPath('data.latitude', -8.506853)->assertJsonPath('data.longitude', 115.262478);
        $removed->assertOk()->assertJsonPath('data.latitude', null)->assertJsonPath('data.longitude', null);
    });

    it('keeps the pin when other fields change', function () {
        $user = actingAsApiUser();
        $memory = Memory::factory()->for($user->personalSpace())->create(['latitude' => -8.506853, 'longitude' => 115.262478]);

        $response = $this->patchJson("/api/v1/memories/{$memory->id}", ['title' => 'Sawah Tegallalang']);

        $response->assertOk()->assertJsonPath('data.latitude', -8.506853)->assertJsonPath('data.longitude', 115.262478);
    });

    it('returns 422 with the stored memory when only one half of the pin is sent', function (array $payload, string $field) {
        $user = actingAsApiUser();
        $memory = Memory::factory()->for($user->personalSpace())->create(['latitude' => -8.506853, 'longitude' => 115.262478]);

        $response = $this->patchJson("/api/v1/memories/{$memory->id}", $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([$field])
            ->assertJsonPath('current.latitude', -8.506853);
        expect($memory->fresh()->longitude)->toBe(115.262478);
    })->with([
        'a latitude alone' => [['latitude' => -6.2], 'longitude'],
        'an empty latitude alone' => [['latitude' => null], 'longitude'],
        'a longitude alone' => [['longitude' => 106.8], 'latitude'],
        'an empty longitude alone' => [['longitude' => null], 'latitude'],
    ]);

    it('links a trip, and unlinks it with null', function () {
        $user = actingAsApiUser();
        $memory = Memory::factory()->for($user->personalSpace())->create();
        $trip = Trip::factory()->for($user->personalSpace())->create();

        $linked = $this->patchJson("/api/v1/memories/{$memory->id}", ['trip_id' => $trip->id]);
        $unlinked = $this->patchJson("/api/v1/memories/{$memory->id}", ['trip_id' => null]);

        $linked->assertOk()->assertJsonPath('data.trip_id', $trip->id);
        $unlinked->assertOk()->assertJsonPath('data.trip_id', null);
    });

    it('returns 422 with the stored memory when the trip is in another space', function () {
        [$user, $partner, $space] = actingAsPartnered();
        $memory = Memory::factory()->for($space)->create();

        $response = $this->patchJson("/api/v1/memories/{$memory->id}", [
            'trip_id' => Trip::factory()->for($user->personalSpace())->create()->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.trip_id.0', 'Perjalanan harus berada di ruang yang sama dengan kenangannya.')
            ->assertJsonPath('current.trip_id', null);
    });

    it('keeps the memory in the space it was created in', function () {
        [$user, $partner, $space] = actingAsPartnered();
        $memory = Memory::factory()->for($space)->create();

        $response = $this->patchJson("/api/v1/memories/{$memory->id}", ['space_id' => $user->personalSpace()->id, 'title' => 'Bulan madu']);

        $response->assertOk()->assertJsonPath('data.space_id', $space->id);
    });

    it('returns 404 with the not_found code for a memory the user cannot reach', function (Closure $id) {
        [$user, $partner] = actingAsPartnered();

        $response = $this->patchJson('/api/v1/memories/'.$id($partner), ['title' => 'Liburan ke Bali']);

        $response->assertNotFound()->assertJsonPath('code', 'not_found')->assertJsonPath('current', null);
    })->with([
        'a memory in the partner\'s personal space' => [fn (User $partner) => Memory::factory()->for($partner->personalSpace())->create()->id],
        'a memory in the space of another couple' => [fn () => Memory::factory()->create()->id],
        'an ID that was never created' => [fn () => newId()],
    ]);

    it('returns 410 with the resource_deleted code for a deleted memory', function () {
        $user = actingAsApiUser();
        $memory = Memory::factory()->for($user->personalSpace())->create();
        $memory->delete();

        $response = $this->patchJson("/api/v1/memories/{$memory->id}", ['title' => 'Liburan ke Bali']);

        $response->assertGone()->assertJsonPath('code', 'resource_deleted');
    });

    it('returns 401 when no token is sent', function () {
        $response = $this->patchJson('/api/v1/memories/'.newId(), ['title' => 'Liburan ke Bali']);

        $response->assertUnauthorized();
    });
});

describe('destroy', function () {
    it('soft deletes the memory with its photos, bumps their updated_at, and queues the removal of their files', function () {
        Queue::fake([DeleteStoredFiles::class]);
        $this->travelTo('2026-10-23 10:00:00');
        $user = actingAsApiUser();
        $memory = Memory::factory()->for($user->personalSpace())->create();
        $first = MemoryPhoto::factory()->for($memory)->create();
        $second = MemoryPhoto::factory()->for($memory)->create();
        $this->travelTo('2026-10-23 11:00:00.250000');

        $response = $this->deleteJson("/api/v1/memories/{$memory->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted($memory);
        $this->assertSoftDeleted($first);
        $this->assertSoftDeleted($second);
        expect($first->fresh()->updated_at->toJSON())->toBe('2026-10-23T11:00:00.250000Z');
        Queue::assertPushed(DeleteStoredFiles::class, fn (DeleteStoredFiles $job) => $job->disk === 'r2'
            && collect($job->paths)->sort()->values()->all() === collect([$first->path, $first->thumb_path, $second->path, $second->thumb_path])->sort()->values()->all());
    });

    it('queues no file removal for a memory without photos', function () {
        Queue::fake([DeleteStoredFiles::class]);
        $user = actingAsApiUser();
        $memory = Memory::factory()->for($user->personalSpace())->create();

        $this->deleteJson("/api/v1/memories/{$memory->id}");

        Queue::assertNotPushed(DeleteStoredFiles::class);
    });

    it('returns 204 again when the delete is repeated', function () {
        Queue::fake([DeleteStoredFiles::class]);
        $user = actingAsApiUser();
        $memory = Memory::factory()->for($user->personalSpace())->create();
        MemoryPhoto::factory()->for($memory)->create();
        $memory->delete();

        $response = $this->deleteJson("/api/v1/memories/{$memory->id}");

        $response->assertNoContent();
        Queue::assertNotPushed(DeleteStoredFiles::class);
    });

    it('returns 404 for a memory in the space of another couple', function () {
        actingAsApiUser();
        $memory = Memory::factory()->create();

        $response = $this->deleteJson("/api/v1/memories/{$memory->id}");

        $response->assertNotFound()->assertJsonPath('code', 'not_found');
        $this->assertNotSoftDeleted($memory);
    });

    it('returns 401 when no token is sent', function () {
        $response = $this->deleteJson('/api/v1/memories/'.newId());

        $response->assertUnauthorized();
    });
});

describe('photos', function () {
    it('returns the photos that are not deleted, by position and then by ID, with new signed URLs', function () {
        Storage::fake('r2');
        [$user, $partner, $space] = actingAsPartnered();
        $memory = Memory::factory()->for($space)->create();
        $last = MemoryPhoto::factory()->for($memory)->create(['position' => 2048]);
        $tiedA = MemoryPhoto::factory()->for($memory)->create(['position' => 1024]);
        $tiedB = MemoryPhoto::factory()->for($memory)->create(['position' => 1024]);
        MemoryPhoto::factory()->for($memory)->create(['position' => 512])->delete();
        [$firstTied, $secondTied] = collect([$tiedA, $tiedB])->sortBy('id')->values()->all();

        $response = $this->getJson("/api/v1/memories/{$memory->id}/photos");

        $response->assertOk()->assertJsonPath('data.*.id', [$firstTied->id, $secondTied->id, $last->id]);
        expect($response->json('data.2.url'))->toContain($last->path)
            ->and($response->json('data.2.thumb_url'))->toContain($last->thumb_path);
    });

    it('returns 410 with the resource_deleted code for a deleted memory', function () {
        $user = actingAsApiUser();
        $memory = Memory::factory()->for($user->personalSpace())->create();
        $memory->delete();

        $response = $this->getJson("/api/v1/memories/{$memory->id}/photos");

        $response->assertGone()->assertJsonPath('code', 'resource_deleted');
    });

    it('returns 404 without current for a memory the user cannot reach', function (Closure $id) {
        [$user, $partner] = actingAsPartnered();

        $response = $this->getJson('/api/v1/memories/'.$id($partner).'/photos');

        $response->assertNotFound()->assertJsonPath('code', 'not_found')->assertJsonMissingPath('current');
    })->with([
        'a memory in the partner\'s personal space' => [fn (User $partner) => Memory::factory()->for($partner->personalSpace())->create()->id],
        'a memory in the space of another couple' => [fn () => Memory::factory()->create()->id],
    ]);

    it('returns 401 when no token is sent', function () {
        $response = $this->getJson('/api/v1/memories/'.newId().'/photos');

        $response->assertUnauthorized();
    });
});
