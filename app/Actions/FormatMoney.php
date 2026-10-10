<?php

namespace App\Actions;

class FormatMoney
{
    /**
     * The currencies shown with a symbol in front of the number.
     *
     * @var array<string, string>
     */
    private const SYMBOLS = [
        'IDR' => 'Rp',
        'JPY' => '¥',
    ];

    /**
     * Format an amount stored in the smallest unit the way the app displays it.
     *
     * The number of decimals comes from the currency table in the config, so
     * rupiah has none. The amount is split with integer arithmetic to stay exact.
     */
    public function handle(int $amount, string $currency): string
    {
        $decimals = config("currencies.decimals.{$currency}");
        $unit = 10 ** $decimals;

        $number = number_format(intdiv($amount, $unit), 0, ',', '.');

        if ($decimals > 0) {
            $number .= ','.str_pad((string) ($amount % $unit), $decimals, '0', STR_PAD_LEFT);
        }

        return isset(self::SYMBOLS[$currency])
            ? self::SYMBOLS[$currency].$number
            : "{$currency} {$number}";
    }
}
