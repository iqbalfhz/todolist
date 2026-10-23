<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DeleteStoredFiles implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before each retry.
     *
     * @var list<int>
     */
    public array $backoff = [60, 600];

    /**
     * Create a new job instance.
     *
     * @param  list<string>  $paths
     */
    public function __construct(public string $disk, public array $paths) {}

    /**
     * Delete the files from the disk.
     *
     * A failure is retried. Deleting a file that is already gone succeeds,
     * so a retry after a partial failure is safe.
     */
    public function handle(): void
    {
        if (! Storage::disk($this->disk)->delete($this->paths)) {
            throw new RuntimeException("Some files could not be deleted from the {$this->disk} disk.");
        }
    }
}
