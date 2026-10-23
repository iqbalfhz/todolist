<?php

namespace App\Actions;

use App\Models\Memory;
use App\Models\MemoryPhoto;
use App\Models\User;
use App\Rules\Iso8601WithOffset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CreateMemoryPhoto
{
    public function __construct(
        private FindWritableMemory $findWritableMemory,
        private EncodeMemoryPhoto $encodeMemoryPhoto,
    ) {}

    /**
     * Store an uploaded photo under the ID the phone chose.
     *
     * The memory is checked before the slow part, re-encoding the photo, and
     * again under a lock while the files are stored and the row is written,
     * so two uploads at once cannot pass the limit together and a memory
     * deleted meanwhile gets no photo.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, string $id, array $data): MemoryPhoto
    {
        $this->ensureRoomIn($this->findWritableMemory->handle($actor, $data['memory_id']));

        $encoded = $this->encodeMemoryPhoto->handle($data['photo']);

        return DB::transaction(function () use ($actor, $id, $data, $encoded): MemoryPhoto {
            $memory = $this->findWritableMemory->handle($actor, $data['memory_id'], lock: true);

            $this->ensureRoomIn($memory);

            $path = MemoryPhoto::pathFor($memory->space_id, $id);
            $thumbPath = MemoryPhoto::thumbPathFor($memory->space_id, $id);
            $disk = Storage::disk(MemoryPhoto::DISK);

            if (! $disk->put($path, $encoded['full']) || ! $disk->put($thumbPath, $encoded['thumb'])) {
                throw new RuntimeException('The memory photo could not be stored.');
            }

            $photo = new MemoryPhoto([
                'position' => $data['position'],
                'width' => $encoded['width'],
                'height' => $encoded['height'],
                'size_bytes' => strlen($encoded['full']) + strlen($encoded['thumb']),
            ]);
            $photo->id = $id;
            $photo->memory_id = $memory->id;
            $photo->space_id = $memory->space_id;
            $photo->uploaded_by = $actor->id;
            $photo->path = $path;
            $photo->thumb_path = $thumbPath;
            $photo->taken_at = isset($data['taken_at']) ? Iso8601WithOffset::toServerTime($data['taken_at']) : null;
            $photo->save();

            return $photo;
        });
    }

    /**
     * Refuse a photo the memory has no room for.
     */
    private function ensureRoomIn(Memory $memory): void
    {
        if ($memory->photos()->count() >= MemoryPhoto::MAX_PER_MEMORY) {
            throw ValidationException::withMessages([
                'photo' => __('api.validation.memory_photo_limit', ['max' => MemoryPhoto::MAX_PER_MEMORY]),
            ]);
        }
    }
}
