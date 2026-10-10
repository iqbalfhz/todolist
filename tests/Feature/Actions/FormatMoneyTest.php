<?php

use App\Actions\FormatMoney;

it('formats an amount with the decimals of its currency', function (int $amount, string $currency, string $expected) {
    $formatted = (new FormatMoney)->handle($amount, $currency);

    expect($formatted)->toBe($expected);
})->with([
    'rupiah, without decimals' => [150000, 'IDR', 'Rp150.000'],
    'yen, without decimals' => [5000, 'JPY', '¥5.000'],
    'dollars, with two decimals' => [1050, 'USD', 'USD 10,50'],
    'less than one euro' => [5, 'EUR', 'EUR 0,05'],
    'a large ringgit amount' => [123456789, 'MYR', 'MYR 1.234.567,89'],
    'the largest amount the API accepts' => [1_000_000_000_000_000, 'IDR', 'Rp1.000.000.000.000.000'],
]);
