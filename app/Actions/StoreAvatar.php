<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Exceptions\ImageException;
use Intervention\Image\ImageManager;
use RuntimeException;

class StoreAvatar
{
    /**
     * The width and height, in pixels, every profile photo is stored at.
     */
    public const SIZE = 512;

    public function __construct(private UpdateProfile $updateProfile) {}

    /**
     * Re-encode the upload as a square JPEG without metadata and make it the user's profile photo.
     */
    public function handle(User $user, UploadedFile $photo): User
    {
        $disk = Storage::disk(User::AVATAR_DISK);
        $path = 'avatars/'.$user->id.'/'.Str::lower((string) Str::ulid()).'.jpg';

        if (! $disk->put($path, $this->squareJpeg($photo))) {
            throw new RuntimeException('The profile photo could not be stored.');
        }

        $previousPath = $user->avatar_path;

        DB::transaction(function () use ($user, $path): void {
            $user->forceFill([
                'avatar_path' => $path,
                'avatar_version' => $user->avatar_version + 1,
            ])->save();

            $this->updateProfile->touchSpaces($user);
        });

        if ($previousPath !== null) {
            $disk->delete($previousPath);
        }

        return $user;
    }

    /**
     * Decode the upload and encode it again, which drops EXIF data such as the location.
     */
    private function squareJpeg(UploadedFile $photo): string
    {
        try {
            return ImageManager::usingDriver(Driver::class)
                ->decodePath($photo->getRealPath())
                ->cover(self::SIZE, self::SIZE)
                ->encode(new JpegEncoder(quality: 85, strip: true))
                ->toString();
        } catch (ImageException) {
            throw ValidationException::withMessages([
                'photo' => __('validation.image', ['attribute' => __('api.attributes.photo')]),
            ]);
        }
    }
}
