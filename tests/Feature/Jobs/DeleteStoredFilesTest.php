<?php

use App\Jobs\DeleteStoredFiles;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

it('deletes the files from the disk, also when one of them is already gone', function () {
    Storage::fake('r2');
    Storage::disk('r2')->put('memories/ruang/foto.jpg', 'isi');
    Storage::disk('r2')->put('memories/ruang/lain.jpg', 'isi');

    DeleteStoredFiles::dispatchSync('r2', ['memories/ruang/foto.jpg', 'memories/ruang/sudah-hilang.jpg']);

    Storage::disk('r2')->assertMissing('memories/ruang/foto.jpg');
    Storage::disk('r2')->assertExists('memories/ruang/lain.jpg');
});

it('fails, so the queue tries again, when the disk could not delete a file', function () {
    $disk = Mockery::mock(Filesystem::class);
    $disk->shouldReceive('delete')->with(['memories/ruang/foto.jpg'])->andReturnFalse();
    Storage::shouldReceive('disk')->with('r2')->andReturn($disk);

    expect(fn () => (new DeleteStoredFiles('r2', ['memories/ruang/foto.jpg']))->handle())
        ->toThrow(RuntimeException::class, 'Some files could not be deleted from the r2 disk.');
});
