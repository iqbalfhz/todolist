<?php

namespace App\Filament\Resources\SpaceInvitations;

use App\Filament\Resources\SpaceInvitations\Pages\ListSpaceInvitations;
use App\Filament\Resources\SpaceInvitations\Tables\SpaceInvitationsTable;
use App\Models\SpaceInvitation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SpaceInvitationResource extends Resource
{
    protected static ?string $model = SpaceInvitation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'code';

    protected static ?string $modelLabel = 'undangan';

    protected static ?string $pluralModelLabel = 'undangan';

    public static function table(Table $table): Table
    {
        return SpaceInvitationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSpaceInvitations::route('/'),
        ];
    }
}
