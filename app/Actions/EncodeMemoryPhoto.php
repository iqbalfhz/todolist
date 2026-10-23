<?php

namespace App\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Exceptions\ImageException;
use Intervention\Image\ImageManager;

class EncodeMemoryPhoto
{
    /**
     * The longest side, in pixels, of the full version.
     */
    public const FULL_SIZE = 2560;

    /**
     * The longest side, in pixels, of the thumbnail.
     */
    public const THUMB_SIZE = 800;

    /**
     * Re-encode the upload as a full version and a thumbnail, both JPEG without metadata.
     *
     * Decoding applies the EXIF orientation, and encoding again drops the
     * EXIF data, such as the location. Neither version is ever enlarged.
     *
     * @return array{full: string, thumb: string, width: int, height: int}
     */
    public function handle(UploadedFile $photo): array
    {
        try {
            $image = ImageManager::usingDriver(Driver::class)
                ->decodePath($photo->getRealPath())
                ->scaleDown(self::FULL_SIZE, self::FULL_SIZE);

            $full = $image->encode(new JpegEncoder(quality: 85, strip: true))->toString();
            $width = $image->width();
            $height = $image->height();

            $thumb = $image->scaleDown(self::THUMB_SIZE, self::THUMB_SIZE)
                ->encode(new JpegEncoder(quality: 80, strip: true))
                ->toString();
        } catch (ImageException) {
            throw ValidationException::withMessages([
                'photo' => __('validation.image', ['attribute' => __('api.attributes.photo')]),
            ]);
        }

        return ['full' => $full, 'thumb' => $thumb, 'width' => $width, 'height' => $height];
    }
}
