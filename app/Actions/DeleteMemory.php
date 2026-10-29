<?php

namespace App\Actions;

use App\Jobs\DeleteStoredFiles;
use App\Models\Memory;
use App\Models\MemoryPhoto;
use Illuminate\Support\Facades\DB;

class DeleteMemory
{
    public function __construct(private DeleteDiscussions $deleteDiscussions) {}

    /**
     * Soft delete the memory together with its photos, comments, and
     * reactions, and remove the files of the photos from R2.
     *
     * The photos are deleted through Eloquent, which also bumps updated_at, so
     * the deletions reach the other phone through sync.
     */
    public function handle(Memory $memory): void
    {
        DB::transaction(function () use ($memory): void {
            // Wait for a photo that is being uploaded to this memory, so it is deleted too.
            Memory::query()->whereKey($memory->id)->lockForUpdate()->first();

            $photos = $memory->photos()->get();

            $memory->photos()->delete();
            $memory->delete();
            $this->deleteDiscussions->handle(Memory::query()->whereKey($memory->id));

            if ($photos->isNotEmpty()) {
                DeleteStoredFiles::dispatch(
                    MemoryPhoto::DISK,
                    $photos->flatMap(fn (MemoryPhoto $photo): array => [$photo->path, $photo->thumb_path])->all(),
                )->afterCommit();
            }
        });
    }
}
