<?php

namespace App\Filament\Resources\Spaces\Tables;

use App\Enums\SpaceType;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SpacesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama'),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge(),
                TextColumn::make('owner.name')
                    ->label('Pemilik')
                    ->searchable(),
                TextColumn::make('owner.email')
                    ->label('Email pemilik')
                    ->searchable(),
                TextColumn::make('members_count')
                    ->label('Anggota')
                    ->counts('members'),
                TextColumn::make('timezone')
                    ->label('Zona waktu'),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Jenis')
                    ->options(SpaceType::class),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
