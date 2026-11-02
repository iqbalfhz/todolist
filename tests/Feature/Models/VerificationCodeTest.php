<?php

use App\Models\VerificationCode;

it('stores a hash keyed with the application key, so a copy of the database alone does not give the codes away', function () {
    config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
    $hash = VerificationCode::hash('482913');
    config(['app.key' => 'base64:'.base64_encode(str_repeat('b', 32))]);

    expect($hash)->toMatch('/^[0-9a-f]{64}$/')
        ->not->toBe(VerificationCode::hash('482913'))
        ->not->toBe(hash('sha256', '482913'));
});
