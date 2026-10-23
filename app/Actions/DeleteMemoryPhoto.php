<?php

namespace App\Actions;

use App\Jobs\DeleteStoredFiles;
use App\Models\MemoryPhoto;

class DeleteMemoryPhoto
{
    /**
     * Soft delete the photo and remove its files from R2.
     *
     * The row stays, so the deletion reaches the other phone through sync.
     */
    public function handle(MemoryPhoto $photo): void
    {
        $photo->delete();

        DeleteStoredFiles::dispatch(MemoryPhoto::DISK, [$photo->path, $photo->thumb_path]);
    }
}
