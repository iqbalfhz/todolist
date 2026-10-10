<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvitationStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Accepted = 'accepted';
    case Revoked = 'revoked';
    case Expired = 'expired';

    /**
     * The name the admin panel shows for the status.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Accepted => 'Terpakai',
            self::Revoked => 'Dicabut',
            self::Expired => 'Kedaluwarsa',
        };
    }

    /**
     * The color of the status badge in the admin panel.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'info',
            self::Accepted => 'success',
            self::Revoked => 'danger',
            self::Expired => 'gray',
        };
    }
}
