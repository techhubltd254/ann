<?php

namespace App\Filament\Resources\CountyHotels;

use App\Filament\Resources\CountyHotels\Pages\CreateCountyHotel;
use App\Filament\Resources\CountyHotels\Pages\EditCountyHotel;
use App\Filament\Resources\CountyHotels\Pages\ListCountyHotels;
use App\Filament\Resources\CountyHotels\Schemas\CountyHotelForm;
use App\Filament\Resources\CountyHotels\Tables\CountyHotelsTable;
use App\Models\CountyHotel;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CountyHotelResource extends Resource
{
    protected static ?string $model = CountyHotel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'County Data';

    public static function form(Schema $schema): Schema
    {
        return CountyHotelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CountyHotelsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCountyHotels::route('/'),
            'create' => CreateCountyHotel::route('/create'),
            'edit' => EditCountyHotel::route('/{record}/edit'),
        ];
    }
}
