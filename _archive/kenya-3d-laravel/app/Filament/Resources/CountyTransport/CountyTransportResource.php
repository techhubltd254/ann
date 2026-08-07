<?php

namespace App\Filament\Resources\CountyTransport;

use App\Filament\Resources\CountyTransport\Pages\CreateCountyTransport;
use App\Filament\Resources\CountyTransport\Pages\EditCountyTransport;
use App\Filament\Resources\CountyTransport\Pages\ListCountyTransport;
use App\Filament\Resources\CountyTransport\Schemas\CountyTransportForm;
use App\Filament\Resources\CountyTransport\Tables\CountyTransportTable;
use App\Models\CountyTransport;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CountyTransportResource extends Resource
{
    protected static ?string $model = CountyTransport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = 'County Data';

    public static function form(Schema $schema): Schema
    {
        return CountyTransportForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CountyTransportTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCountyTransport::route('/'),
            'create' => CreateCountyTransport::route('/create'),
            'edit' => EditCountyTransport::route('/{record}/edit'),
        ];
    }
}
