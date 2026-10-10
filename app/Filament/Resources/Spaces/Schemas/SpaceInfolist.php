<?php

namespace App\Filament\Resources\Spaces\Schemas;

use App\Enums\SpaceRole;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SpaceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Nama'),
                TextEntry::make('type')
                    ->label('Jenis')
                    ->badge(),
                TextEntry::make('timezone')
                    ->label('Zona waktu'),
                TextEntry::make('created_at')
                    ->label('Dibuat')
                    ->dateTime(),
                RepeatableEntry::make('members')
                    ->label('Anggota')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama'),
                        TextEntry::make('email')
                            ->label('Email'),
                        TextEntry::make('pivot.role')
                            ->label('Peran')
                            ->formatStateUsing(fn (string $state): string => SpaceRole::from($state)->getLabel()),
                        TextEntry::make('pivot.joined_at')
                            ->label('Bergabung')
                            ->dateTime(),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
            ]);
    }
}
