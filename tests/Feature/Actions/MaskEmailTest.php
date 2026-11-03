<?php

use App\Actions\MaskEmail;

it('keeps the first characters before the @ and the domain', function (string $email, string $masked) {
    expect((new MaskEmail)->handle($email))->toBe($masked);
})->with([
    'a long name' => ['baru.sekali@gmail.com', 'ba***@gmail.com'],
    'three characters' => ['abc@email.com', 'ab***@email.com'],
    'two characters, so only one stays' => ['ab@email.com', 'a***@email.com'],
    'one character' => ['a@email.com', 'a***@email.com'],
    'an @ inside quotes before the last one' => ['"a@b"@email.com', '"a***@email.com'],
]);
