<?php

use App\Jobs\DeleteStoredDirectory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

it('deletes the directory with every file in it, and nothing outside it', function () {
    Storage::fake('r2');
    Storage::disk('r2')->put('memories/ruang/foto.jpg', 'isi');
    Storage::disk('r2')->put('memories/ruang/foto-thumb.jpg', 'isi');
    Storage::disk('r2')->put('memories/ruang-lain/foto.jpg', 'isi');

    DeleteStoredDirectory::dispatchSync('r2', 'memories/ruang');

    Storage::disk('r2')->assertMissing(['memories/ruang/foto.jpg', 'memories/ruang/foto-thumb.jpg']);
    Storage::disk('r2')->assertExists('memories/ruang-lain/foto.jpg');
});

it('succeeds when the directory is already gone, so a retry is safe', function () {
    Storage::fake('r2');

    DeleteStoredDirectory::dispatchSync('r2', 'memories/sudah-hilang');
})->throwsNoExceptions();

it('fails, so the queue tries again, when the disk could not delete the directory', function () {
    $disk = Mockery::mock(Filesystem::class);
    $disk->shouldReceive('deleteDirectory')->with('memories/ruang')->andReturnFalse();
    Storage::shouldReceive('disk')->with('r2')->andReturn($disk);

    expect(fn () => (new DeleteStoredDirectory('r2', 'memories/ruang'))->handle())
        ->toThrow(RuntimeException::class, 'The memories/ruang directory could not be deleted from the r2 disk.');
});
