<?php

namespace App\Models;

use App\Models\Concerns\HasMicrosecondTimestamps;
use App\Models\Concerns\NotifiesSpaceChanges;
use Database\Factories\MemoryPhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'memory_id',
    'space_id',
    'uploaded_by',
    'path',
    'thumb_path',
    'size_bytes',
    'width',
    'height',
    'taken_at',
    'position',
])]
class MemoryPhoto extends Model
{
    /** @use HasFactory<MemoryPhotoFactory> */
    use HasFactory, HasMicrosecondTimestamps, HasUlids, NotifiesSpaceChanges, SoftDeletes;

    /**
     * The filesystem disk that holds memory photos, the same one as profile photos.
     */
    public const DISK = 'r2';

    /**
     * The most photos a memory can hold.
     */
    public const MAX_PER_MEMORY = 50;

    /**
     * Get the path of the full version of a photo.
     *
     * The path follows from the IDs alone, so a repeated upload overwrites
     * the same files instead of leaving copies behind.
     */
    public static function pathFor(string $spaceId, string $id): string
    {
        return self::directoryFor($spaceId)."/{$id}.jpg";
    }

    /**
     * Get the path of the thumbnail of a photo.
     */
    public static function thumbPathFor(string $spaceId, string $id): string
    {
        return self::directoryFor($spaceId)."/{$id}-thumb.jpg";
    }

    /**
     * Get the directory that holds the photos of every memory in a space.
     */
    public static function directoryFor(string $spaceId): string
    {
        return "memories/{$spaceId}";
    }

    /**
     * Get the memory the photo belongs to.
     */
    public function memory(): BelongsTo
    {
        return $this->belongsTo(Memory::class);
    }

    /**
     * Get the space the photo belongs to.
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    /**
     * Get a signed URL for the full version, or null once the photo is deleted.
     */
    public function url(): ?string
    {
        return $this->signedUrl($this->path);
    }

    /**
     * Get a signed URL for the thumbnail, or null once the photo is deleted.
     */
    public function thumbUrl(): ?string
    {
        return $this->signedUrl($this->thumb_path);
    }

    /**
     * Get a signed URL valid for seven days, the longest R2 allows.
     *
     * The files of a deleted photo are removed from R2, so it gets no URL.
     */
    private function signedUrl(string $path): ?string
    {
        if ($this->trashed()) {
            return null;
        }

        return Storage::disk(self::DISK)->temporaryUrl($path, now()->addDays(7));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'taken_at' => 'datetime',
            'position' => 'float',
        ];
    }
}
