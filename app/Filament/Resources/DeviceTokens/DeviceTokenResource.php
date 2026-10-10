<?php

namespace App\Filament\Resources\DeviceTokens;

use App\Filament\Resources\DeviceTokens\Pages\ListDeviceTokens;
use App\Filament\Resources\DeviceTokens\Tables\DeviceTokensTable;
use App\Models\DeviceToken;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DeviceTokenResource extends Resource
{
    protected static ?string $model = DeviceToken::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'token perangkat';

    protected static ?string $pluralModelLabel = 'token perangkat';

    public static function table(Table $table): Table
    {
        return DeviceTokensTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeviceTokens::route('/'),
        ];
    }
}
