<?php

use App\Jobs\DeleteStoredFiles;
use App\Models\Memory;
use App\Models\MemoryPhoto;
use App\Models\Space;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

describe('store', function () {
    it('stores the photo with the ID from the phone and returns 201', function () {
        Storage::fake('r2');
        $this->travelTo('2026-10-23 10:00:00.123456');
        [$user, $partner, $space] = actingAsPartnered();
        $memory = Memory::factory()->for($space)->create(['created_by' => $partner->id]);
        $id = newId();

        $response = $this->post("/api/v1/memory-photos/{$id}", [
            'memory_id' => $memory->id,
            'photo' => UploadedFile::fake()->image('foto.jpg', 640, 480),
            'position' => 1024,
            'taken_at' => '2026-10-20T10:12:45+07:00',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.memory_id', $memory->id)
            ->assertJsonPath('data.space_id', $space->id)
            ->assertJsonPath('data.uploaded_by', $user->id)
            ->assertJsonPath('data.position', 1024)
            ->assertJsonPath('data.width', 640)
            ->assertJsonPath('data.height', 480)
            ->assertJsonPath('data.taken_at', '2026-10-20T03:12:45.000000Z')
            ->assertJsonPath('data.created_at', '2026-10-23T10:00:00.123456Z')
            ->assertJsonPath('data.deleted_at', null);
        expect($response->json('data.url'))->toContain("memories/{$space->id}/{$id}.jpg")
            ->and($response->json('data.thumb_url'))->toContain("memories/{$space->id}/{$id}-thumb.jpg");
        Storage::disk('r2')->assertExists(["memories/{$space->id}/{$id}.jpg", "memories/{$space->id}/{$id}-thumb.jpg"]);
        $this->assertDatabaseHas('memory_photos', ['id' => $id, 'memory_id' => $memory->id, 'space_id' => $space->id]);
    });

    it('shrinks the full version to 2560 px and the thumbnail to 800 px on the longest side', function () {
        Storage::fake('r2');
        $user = actingAsApiUser();
        $memory = Memory::factory()->for($user->personalSpace())->create();
        $id = newId();

        $response = $this->post("/api/v1/memory-photos/{$id}", [
            'memory_id' => $memory->id,
            'photo' => UploadedFile::fake()->image('foto.jpg', 3000, 2000),
            'position' => 1024,
        ]);

        $response->assertCreated()->assertJsonPath('data.width', 2560)->assertJsonPath('data.height', 1707);
        $photo = MemoryPhoto::query()->findOrFail($id);
        expect(getimagesizefromstring(Storage::disk('r2')->get($photo->path)))->toMatchArray([0 => 2560, 1 => 1707])
            ->and(getimagesizefromstring(Storage::disk('r2')->get($photo->thumb_path)))->toMatchArray([0 => 800, 1 => 533]);
    });

    it('keeps a photo smaller than the thumbnail at its own size', function () {
        Storage::fake('r2');
        $user = actingAsApiUser();
        $memory = Memory::factory()->for($user->personalSpace())->create();
        $id = newId();

        $this->post("/api/v1/memory-photos/{$id}", [
            'memory_id' => $memory->id,
            'photo' => UploadedFile::fake()->image('kecil.jpg', 320, 240),
            'position' => 1024,
        ]);

        $photo = MemoryPhoto::query()->findOrFail($id);
        expect(getimagesizefromstring(Storage::disk('r2')->get($photo->path)))->toMatchArray([0 => 320, 1 => 240])
            ->and(getimagesizefromstring(Storage::disk('r2')->get($photo->thumb_path)))->toMatchArray([0 => 320, 1 => 240]);
    });

    it('stores every accepted format as JPEG', function (string $filename) {
        Storage::fake('r2');
        $user = actingAsApiUser();
        $memory = Memory::factory()->for($user->personalSpace())->create();
        $id = newId();

        $this->post("/api/v1/memory-photos/{$id}", [
            'memory_id' => $memory->id,
            'photo' => UploadedFile::fake()->image($filename),
            'position' => 1024,
        ]);

        $photo = MemoryPhoto::query()->findOrFail($id);
        expect(getimagesizefromstring(Storage::disk('r2')->get($photo->path)))->toMatchArray(['mime' => 'image/jpeg'])
            ->and(getimagesizefromstring(Storage::disk('r2')->get($photo->thumb_path)))->toMatchArray(['mime' => 'image/jpeg']);
    })->with(['foto.jpg', 'foto.png', 'foto.webp']);

    it('drops the EXIF data of the upload from both versions', function () {
        Storage::fake('r2');
        $user = actingAsApiUser();
        $memory = Memory::factory()->for($user->personalSpace())->create();
        $id = newId();

        $this->post("/api/v1/memory-photos/{$id}", [
            'memory_id' => $memory->id,
            'photo' => UploadedFile::fake()->createWithContent('foto.jpg', jpegWithExif('LOKASI-RAHASIA')),
            'position' => 1024,
        ]);

        $photo = MemoryPhoto::query()->findOrFail($id);
        expect(Storage::disk('r2')->get($photo->path))->not->toContain('LOKASI-RAHASIA')
            ->and(Storage::disk('r2')->get($photo->thumb_path))->not->toContain('LOKASI-RAHASIA');
    });

    it('turns the photo the way its EXIF orientation says before dropping it', function () {
        Storage::fake('r2');
        $user = actingAsApiUser();
        $memory = Memory::factory()->for($user->personalSpace())->create();
        $id = newId();

        $response = $this->post("/api/v1/memory-photos/{$id}", [
            'memory_id' => $memory->id,
            'photo' => UploadedFile::fake()->createWithContent('tegak.jpg', jpegWithOrientation(6)),
            'position' => 1024,
        ]);

        $response->assertCreated()->assertJsonPath('data.width', 48)->assertJsonPath('data.height', 64);
    });

    it('returns 200 with the stored photo and keeps its files when the upload is repeated', function () {
        Storage::fake('r2');
        $user = actingAsApiUser();
        $memory = Memory::factory()->for($user->personalSpace())->create();
        $id = newId();
        $upload = fn (int $width) => $this->post("/api/v1/memory-photos/{$id}", [
            'memory_id' => $memory->id,
            'photo' => UploadedFile::fake()->image('foto.jpg', $width, 480),
            'position' => 1024,
        ]);
        $upload(640);
        $stored = Storage::disk('r2')->get(MemoryPhoto::query()->findOrFail($id)->path);

        $response = $upload(800);

        $response->assertOk()->assertJsonPath('data.width', 640);
        expect(Storage::disk('r2')->get(MemoryPhoto::query()->findOrFail($id)->path))->toBe($stored);
        $this->assertDatabaseCount('memory_photos', 1);
    });

    it('returns 410 with the resource_deleted code when the ID belongs to a deleted photo', function () {
        Storage::fake('r2');
        $user = actingAsApiUser();
        $photo = MemoryPhoto::factory()->for(Memory::factory()->for($user->personalSpace()))->create();
        $photo->delete();

        $response = $this->post("/api/v1/memory-photos/{$photo->id}", [
            'memory_id' => $photo->memory_id,
            'photo' => UploadedFile::fake()->image('foto.jpg'),
            'position' => 1024,
        ]);

        $response->assertGone()->assertJsonPath('code', 'resource_deleted');
    });

    it('returns 409 with the id_conflict code when the ID is used in another space', function (Closure $existingSpace) {
        Storage::fake('r2');
        $user = actingAsApiUser();
        $photo = MemoryPhoto::factory()->for(Memory::factory()->for($existingSpace($user)))->create();

        $response = $this->post("/api/v1/memory-photos/{$photo->id}", [
            'memory_id' => Memory::factory()->for($user->personalSpace())->create()->id,
            'photo' => UploadedFile::fake()->image('foto.jpg'),
            'position' => 1024,
        ]);

        $response->assertConflict()->assertJsonPath('code', 'id_conflict');
        $this->assertDatabaseCount('memory_photos', 1);
    })->with([
        'the space shared with the partner' => [fn (User $user) => sharedSpaceBetween($user, User::factory()->create())],
        'a space of another couple' => [fn () => Space::factory()->create()],
    ]);

    it('returns 404 and stores nothing when the memory is not one the user can reach', function (Closure $memoryId) {
        Storage::fake('r2');
        [$user, $partner] = actingAsPartnered();

        $response = $this->post('/api/v1/memory-photos/'.newId(), [
            'memory_id' => $memoryId($partner),
            'photo' => UploadedFile::fake()->image('foto.jpg'),
            'position' => 1024,
        ]);

        $response->assertNotFound()->assertJsonPath('code', 'not_found')->assertJsonPath('current', null);
        $this->assertDatabaseEmpty('memory_photos');
        expect(Storage::disk('r2')->allFiles())->toBe([]);
    })->with([
        'a memory in the partner\'s personal space' => [fn (User $partner) => Memory::factory()->for($partner->personalSpace())->create()->id],
        'a memory in the space of another couple' => [fn () => Memory::factory()->create()->id],
        'a memory that was never created' => [fn () => newId()],
    ]);

    it('returns 409 with the parent_deleted code and stores nothing when the memory was deleted', function () {
        Storage::fake('r2');
        $user = actingAsApiUser();
        $memory = Memory::factory()->for($user->personalSpace())->create();
        $memory->delete();

        $response = $this->post('/api/v1/memory-photos/'.newId(), [
            'memory_id' => $memory->id,
            'photo' => UploadedFile::fake()->image('foto.jpg'),
            'position' => 1024,
        ]);

        $response->assertConflict()
            ->assertJsonPath('code', 'parent_deleted')
            ->assertJsonPath('message', 'Kenangan ini sudah dihapus.')
            ->assertJsonPath('current', null);
        expect(Storage::disk('r2')->allFiles())->toBe([]);
    });

    it('returns 422 and stores nothing for the 51st photo of a memory', function () {
        Storage::fake('r2');
        $user = actingAsApiUser();
        $memory = Memory::factory()->for($user->personalSpace())->create();
        MemoryPhoto::factory()->count(50)->for($memory)->create();

        $response = $this->post('/api/v1/memory-photos/'.newId(), [
            'memory_id' => $memory->id,
            'photo' => UploadedFile::fake()->image('foto.jpg'),
            'position' => 1024,
        ]);

        $response->assertUnprocessable()->assertJsonPath('errors.photo.0', 'Satu kenangan paling banyak 50 foto.');
        $this->assertDatabaseCount('memory_photos', 50);
        expect(Storage::disk('r2')->allFiles())->toBe([]);
    });

    it('does not count deleted photos toward the limit', function () {
        Storage::fake('r2');
        $user = actingAsApiUser();
        $memory = Memory::factory()->for($user->personalSpace())->create();
        MemoryPhoto::factory()->count(49)->for($memory)->create();
        MemoryPhoto::factory()->for($memory)->create()->delete();

        $response = $this->post('/api/v1/memory-photos/'.newId(), [
            'memory_id' => $memory->id,
            'photo' => UploadedFile::fake()->image('foto.jpg'),
            'position' => 1024,
        ]);

        $response->assertCreated();
    });

    it('returns 422 for invalid values', function (array $overrides, string $field) {
        Storage::fake('r2');
        $user = actingAsApiUser();
        $memory = Memory::factory()->for($user->personalSpace())->create();

        $response = $this->postJson('/api/v1/memory-photos/'.newId(), [
            'memory_id' => $memory->id,
            'photo' => UploadedFile::fake()->image('foto.jpg'),
            'position' => 1024,
            ...$overrides,
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonPath('current', null)
            ->assertJsonValidationErrors([$field]);
        $this->assertDatabaseEmpty('memory_photos');
    })->with([
        'no memory' => [['memory_id' => null], 'memory_id'],
        'no file' => [['photo' => null], 'photo'],
        'a PDF' => [['photo' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf')], 'photo'],
        'a GIF' => [['photo' => UploadedFile::fake()->image('animasi.gif')], 'photo'],
        'a text file named like a photo' => [['photo' => UploadedFile::fake()->createWithContent('palsu.jpg', 'bukan gambar')], 'photo'],
        'a photo larger than 10 MB' => [['photo' => UploadedFile::fake()->image('besar.jpg')->size(10241)], 'photo'],
        'a photo wider than 4096 px' => [['photo' => UploadedFile::fake()->image('lebar.jpg', 4097, 10)], 'photo'],
        'no position' => [['position' => null], 'position'],
        'a position that is not a number' => [['position' => 'pertama'], 'position'],
        'a position too far from zero to store' => [['position' => '1e400'], 'position'],
        'a time without Z or an offset' => [['taken_at' => '2026-10-20T10:12:45'], 'taken_at'],
    ]);

    it('returns 500 with the server_error code and stores no row when the storage refuses the photo', function () {
        config([
            'filesystems.disks.r2.key' => 'kunci-akses',
            'filesystems.disks.r2.secret' => 'kunci-rahasia',
            'filesystems.disks.r2.bucket' => '',
            'filesystems.disks.r2.endpoint' => 'https://akun.r2.cloudflarestorage.com',
        ]);
        $user = actingAsApiUser();
        $memory = Memory::factory()->for($user->personalSpace())->create();

        $response = $this->post('/api/v1/memory-photos/'.newId(), [
            'memory_id' => $memory->id,
            'photo' => UploadedFile::fake()->image('foto.jpg'),
            'position' => 1024,
        ]);

        $response->assertInternalServerError()->assertJsonPath('code', 'server_error');
        $this->assertDatabaseEmpty('memory_photos');
    });

    it('returns 401 when no token is sent', function () {
        $response = $this->postJson('/api/v1/memory-photos/'.newId());

        $response->assertUnauthorized();
    });
});

describe('update', function () {
    it('moves the photo to another position and ignores the other fields', function () {
        Storage::fake('r2');
        [$user, $partner, $space] = actingAsPartnered();
        $photo = MemoryPhoto::factory()->for(Memory::factory()->for($space))->create(['position' => 2048, 'width' => 2560]);

        $response = $this->patchJson("/api/v1/memory-photos/{$photo->id}", ['position' => 512.5, 'width' => 10]);

        $response->assertOk()->assertJsonPath('data.position', 512.5)->assertJsonPath('data.width', 2560);
        expect($photo->fresh()->position)->toBe(512.5);
    });

    it('returns 422 with the stored photo for a position that is not a number it can store', function (string $position) {
        Storage::fake('r2');
        $user = actingAsApiUser();
        $photo = MemoryPhoto::factory()->for(Memory::factory()->for($user->personalSpace()))->create(['position' => 2048]);

        $response = $this->patchJson("/api/v1/memory-photos/{$photo->id}", ['position' => $position]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['position'])
            ->assertJsonPath('current.position', 2048);
    })->with(['a word' => ['pertama'], 'a number too far from zero' => ['1e400']]);

    it('returns 404 with the not_found code for a photo the user cannot reach', function (Closure $id) {
        [$user, $partner] = actingAsPartnered();

        $response = $this->patchJson('/api/v1/memory-photos/'.$id($partner), ['position' => 512]);

        $response->assertNotFound()->assertJsonPath('code', 'not_found')->assertJsonPath('current', null);
    })->with([
        'a photo in the partner\'s personal space' => [fn (User $partner) => MemoryPhoto::factory()->for(Memory::factory()->for($partner->personalSpace()))->create()->id],
        'a photo in the space of another couple' => [fn () => MemoryPhoto::factory()->create()->id],
        'an ID that was never created' => [fn () => newId()],
    ]);

    it('returns 410 with the resource_deleted code for a deleted photo', function () {
        $user = actingAsApiUser();
        $photo = MemoryPhoto::factory()->for(Memory::factory()->for($user->personalSpace()))->create();
        $photo->delete();

        $response = $this->patchJson("/api/v1/memory-photos/{$photo->id}", ['position' => 512]);

        $response->assertGone()->assertJsonPath('code', 'resource_deleted');
    });

    it('returns 401 when no token is sent', function () {
        $response = $this->patchJson('/api/v1/memory-photos/'.newId(), ['position' => 512]);

        $response->assertUnauthorized();
    });
});

describe('destroy', function () {
    it('soft deletes the photo and queues the removal of its files', function () {
        Queue::fake([DeleteStoredFiles::class]);
        $user = actingAsApiUser();
        $photo = MemoryPhoto::factory()->for(Memory::factory()->for($user->personalSpace()))->create();

        $response = $this->deleteJson("/api/v1/memory-photos/{$photo->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted($photo);
        Queue::assertPushed(DeleteStoredFiles::class, fn (DeleteStoredFiles $job) => $job->disk === 'r2'
            && $job->paths === [$photo->path, $photo->thumb_path]);
    });

    it('returns 204 again and queues nothing when the delete is repeated', function () {
        Queue::fake([DeleteStoredFiles::class]);
        $user = actingAsApiUser();
        $photo = MemoryPhoto::factory()->for(Memory::factory()->for($user->personalSpace()))->create();
        $photo->delete();

        $response = $this->deleteJson("/api/v1/memory-photos/{$photo->id}");

        $response->assertNoContent();
        Queue::assertNotPushed(DeleteStoredFiles::class);
    });

    it('returns 404 for a photo in the space of another couple', function () {
        actingAsApiUser();
        $photo = MemoryPhoto::factory()->create();

        $response = $this->deleteJson("/api/v1/memory-photos/{$photo->id}");

        $response->assertNotFound()->assertJsonPath('code', 'not_found');
        $this->assertNotSoftDeleted($photo);
    });

    it('returns 401 when no token is sent', function () {
        $response = $this->deleteJson('/api/v1/memory-photos/'.newId());

        $response->assertUnauthorized();
    });
});
