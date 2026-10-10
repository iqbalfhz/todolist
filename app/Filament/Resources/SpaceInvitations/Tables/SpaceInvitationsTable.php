<?php

namespace App\Filament\Resources\SpaceInvitations\Tables;

use App\Filament\Resources\SpaceInvitations\Actions\RevokeInvitationAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SpaceInvitationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable(),
                TextColumn::make('creator.name')
                    ->label('Pembuat')
                    ->searchable(),
                TextColumn::make('creator.email')
                    ->label('Email pembuat')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('expires_at')
                    ->label('Berlaku sampai')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('acceptor.name')
                    ->label('Diterima oleh')
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('active')
                    ->label('Hanya yang aktif')
                    ->query(fn (Builder $query): Builder => $query->active()),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                RevokeInvitationAction::make(),
            ]);
    }
}
