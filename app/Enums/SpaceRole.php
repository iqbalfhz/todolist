<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SpaceRole: string implements HasLabel
{
    case Owner = 'owner';
    case Member = 'member';

    /**
     * The name the admin panel shows for the role.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Owner => 'Pemilik',
            self::Member => 'Anggota',
        };
    }
}
