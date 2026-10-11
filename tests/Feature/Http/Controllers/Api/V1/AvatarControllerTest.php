<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Build a small JPEG that carries the given text in its EXIF "Make" tag.
 */
function jpegWithExif(string $make): string
{
    ob_start();
    imagejpeg(imagecreatetruecolor(64, 48));
    $jpeg = ob_get_clean();

    $value = $make."\0";
    $tiff = "II*\0".pack('V', 8)
        .pack('v', 1)
        .pack('vvVV', 0x010F, 2, strlen($value), 26)
        .pack('V', 0)
        .$value;
    $payload = "Exif\0\0".$tiff;

    return substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($payload) + 2).$payload.substr($jpeg, 2);
}

describe('store', function () {
    it('stores the photo as a 512 by 512 JPEG and returns the profile', function (string $filename) {
        Storage::fake('r2');
        $user = actingAsApiUser();

        $response = $this->post('/api/v1/me/avatar', [
            'photo' => UploadedFile::fake()->image($filename, 800, 600),
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.avatar_version', 1);
        $stored = Storage::disk('r2')->get($user->fresh()->avatar_path);
        expect(getimagesizefromstring($stored))->toMatchArray([0 => 512, 1 => 512, 'mime' => 'image/jpeg']);
    })->with(['foto.jpg', 'foto.png', 'foto.webp']);

    it('returns a signed URL for the stored photo', function () {
        Storage::fake('r2');
        $user = actingAsApiUser();

        $response = $this->post('/api/v1/me/avatar', ['photo' => UploadedFile::fake()->image('foto.jpg')]);

        expect($response->json('data.avatar_url'))->toContain($user->fresh()->avatar_path);
    });

    it('drops the EXIF data of the upload', function () {
        Storage::fake('r2');
        $user = actingAsApiUser();
        $photo = UploadedFile::fake()->createWithContent('foto.jpg', jpegWithExif('LOKASI-RAHASIA'));

        $this->post('/api/v1/me/avatar', ['photo' => $photo]);

        expect(Storage::disk('r2')->get($user->fresh()->avatar_path))->not->toContain('LOKASI-RAHASIA');
    });

    it('replaces the previous photo and raises the version again', function () {
        Storage::fake('r2');
        $user = actingAsApiUser();
        $this->post('/api/v1/me/avatar', ['photo' => UploadedFile::fake()->image('lama.jpg')]);
        $previousPath = $user->fresh()->avatar_path;

        $response = $this->post('/api/v1/me/avatar', ['photo' => UploadedFile::fake()->image('baru.jpg')]);

        $response->assertOk()->assertJsonPath('data.avatar_version', 2);
        Storage::disk('r2')->assertMissing($previousPath);
        Storage::disk('r2')->assertExists($user->fresh()->avatar_path);
    });

    it('bumps the spaces of the user, so the partner syncs the new photo', function () {
        Storage::fake('r2');
        $this->travelTo('2026-10-08 10:00:00');
        $user = actingAsApiUser();
        $sharedSpace = sharedSpaceBetween($user, User::factory()->create());
        $this->travelTo('2026-10-08 11:00:00');

        $this->post('/api/v1/me/avatar', ['photo' => UploadedFile::fake()->image('foto.jpg')]);

        expect($sharedSpace->fresh()->updated_at->toJSON())->toBe('2026-10-08T11:00:00.000000Z');
    });

    it('returns 500 with the server_error code and keeps the profile unchanged when the storage refuses the photo', function () {
        config([
            'filesystems.disks.r2.key' => 'kunci-akses',
            'filesystems.disks.r2.secret' => 'kunci-rahasia',
            'filesystems.disks.r2.bucket' => '',
            'filesystems.disks.r2.endpoint' => 'https://akun.r2.cloudflarestorage.com',
        ]);
        $user = actingAsApiUser();

        $response = $this->post('/api/v1/me/avatar', ['photo' => UploadedFile::fake()->image('foto.jpg')]);

        $response->assertInternalServerError()->assertJsonPath('code', 'server_error');
        expect($user->fresh())->avatar_path->toBeNull()->avatar_version->toBe(0);
    });

    it('returns 422 for an upload that is not an accepted photo', function (?UploadedFile $photo) {
        Storage::fake('r2');
        $user = actingAsApiUser();

        $response = $this->postJson('/api/v1/me/avatar', ['photo' => $photo]);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonValidationErrors(['photo']);
        expect($user->fresh())->avatar_path->toBeNull()->avatar_version->toBe(0);
    })->with([
        'no file' => [null],
        'a PDF' => [fn () => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf')],
        'a GIF' => [fn () => UploadedFile::fake()->image('animasi.gif')],
        'a photo larger than 2 MB' => [fn () => UploadedFile::fake()->image('besar.jpg')->size(2049)],
        'a text file named like a photo' => [fn () => UploadedFile::fake()->createWithContent('palsu.jpg', 'bukan gambar')],
    ]);

    it('returns 401 when no token is sent', function () {
        $response = $this->postJson('/api/v1/me/avatar');

        $response->assertUnauthorized();
    });
});

describe('destroy', function () {
    it('removes the photo and returns 204', function () {
        Storage::fake('r2');
        $user = actingAsApiUser();
        $this->post('/api/v1/me/avatar', ['photo' => UploadedFile::fake()->image('foto.jpg')]);
        $path = $user->fresh()->avatar_path;

        $response = $this->deleteJson('/api/v1/me/avatar');

        $response->assertNoContent();
        Storage::disk('r2')->assertMissing($path);
        $this->getJson('/api/v1/me')->assertJsonPath('data.avatar_url', null);
    });

    it('returns 204 when there is no photo to remove', function () {
        Storage::fake('r2');
        actingAsApiUser();

        $response = $this->deleteJson('/api/v1/me/avatar');

        $response->assertNoContent();
    });

    it('bumps the spaces of the user, so the partner stops showing the photo', function () {
        Storage::fake('r2');
        $this->travelTo('2026-10-08 10:00:00');
        $user = actingAsApiUser();
        $this->post('/api/v1/me/avatar', ['photo' => UploadedFile::fake()->image('foto.jpg')]);
        $this->travelTo('2026-10-08 11:00:00');

        $this->deleteJson('/api/v1/me/avatar');

        expect($user->personalSpace()->updated_at->toJSON())->toBe('2026-10-08T11:00:00.000000Z');
    });

    it('returns 401 when no token is sent', function () {
        $response = $this->deleteJson('/api/v1/me/avatar');

        $response->assertUnauthorized();
    });
});
