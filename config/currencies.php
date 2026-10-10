<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    |
    | The ISO 4217 codes the app accepts and how many decimal digits each one
    | has. Amounts are stored as integers in the smallest unit. These values
    | come from the API contract, not from ISO 4217 or NumberFormatter: IDR
    | deliberately has no decimals.
    |
    */

    'decimals' => [
        'IDR' => 0,
        'JPY' => 0,
        'USD' => 2,
        'SGD' => 2,
        'MYR' => 2,
        'EUR' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Largest Amount
    |--------------------------------------------------------------------------
    |
    | The largest amount, in the smallest unit, that any money field accepts.
    |
    */

    'max_amount' => 1_000_000_000_000_000,

];
