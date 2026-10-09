<?php

use Illuminate\Support\Facades\Validator;

it('reports validation errors in Indonesian', function () {
    $validator = Validator::make(['nama' => ''], ['nama' => 'required']);

    $message = $validator->errors()->first('nama');

    expect($message)->toBe('Nama wajib diisi.');
});
