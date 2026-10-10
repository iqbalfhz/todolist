<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SpaceType: string implements HasLabel
{
    case Personal = 'personal';
    case Shared = 'shared';

    /**
     * The name the admin panel shows for the type.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Personal => 'Pribadi',
            self::Shared => 'Bersama',
        };
    }
}
