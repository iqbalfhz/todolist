<?php

namespace App\Filament\Resources\Spaces;

use App\Filament\Resources\Spaces\Pages\ListSpaces;
use App\Filament\Resources\Spaces\Pages\ViewSpace;
use App\Filament\Resources\Spaces\Schemas\SpaceInfolist;
use App\Filament\Resources\Spaces\Tables\SpacesTable;
use App\Models\Space;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Spaces and their members, to look at only. What a space holds (lists,
 * tasks, savings) belongs to its members and is never shown here.
 */
class SpaceResource extends Resource
{
    protected static ?string $model = Space::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'ruang';

    protected static ?string $pluralModelLabel = 'ruang';

    public static function infolist(Schema $schema): Schema
    {
        return SpaceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SpacesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSpaces::route('/'),
            'view' => ViewSpace::route('/{record}'),
        ];
    }
}
