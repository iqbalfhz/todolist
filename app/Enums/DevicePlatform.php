<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DevicePlatform: string implements HasLabel
{
    case Android = 'android';
    case Ios = 'ios';

    /**
     * The name the admin panel shows for the platform.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Android => 'Android',
            self::Ios => 'iOS',
        };
    }
}
