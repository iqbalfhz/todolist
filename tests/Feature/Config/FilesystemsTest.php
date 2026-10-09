<?php

use Illuminate\Support\Facades\Storage;

it('creates signed temporary URLs on the r2 disk', function () {
    config([
        'filesystems.disks.r2.key' => 'kunci-akses',
        'filesystems.disks.r2.secret' => 'kunci-rahasia',
        'filesystems.disks.r2.bucket' => 'foto',
        'filesystems.disks.r2.endpoint' => 'https://akun.r2.cloudflarestorage.com',
    ]);

    $url = Storage::disk('r2')->temporaryUrl('profil/contoh.jpg', now()->addMinutes(5));

    expect($url)
        ->toStartWith('https://foto.akun.r2.cloudflarestorage.com/profil/contoh.jpg?')
        ->toMatch('/X-Amz-Credential=kunci-akses%2F\d{8}%2Fauto%2Fs3%2Faws4_request/')
        ->toContain('X-Amz-Signature=');
});
