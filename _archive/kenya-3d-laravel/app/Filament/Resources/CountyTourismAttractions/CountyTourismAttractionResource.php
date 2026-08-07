<?php

namespace App\Filament\Resources\CountyTourismAttractions;

use App\Filament\Resources\CountyTourismAttractions\Pages\CreateCountyTourismAttraction;
use App\Filament\Resources\CountyTourismAttractions\Pages\EditCountyTourismAttraction;
use App\Filament\Resources\CountyTourismAttractions\Pages\ListCountyTourismAttractions;
use App\Filament\Resources\CountyTourismAttractions\Schemas\CountyTourismAttractionForm;
use App\Filament\Resources\CountyTourismAttractions\Tables\CountyTourismAttractionsTable;
use App\Models\CountyTourismAttraction;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CountyTourismAttractionResource extends Resource
{
    protected static ?string $model = CountyTourismAttraction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'County Data';

    public static function form(Schema $schema): Schema
    {
        return CountyTourismAttractionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CountyTourismAttractionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCountyTourismAttractions::route('/'),
            'create' => CreateCountyTourismAttraction::route('/create'),
            'edit' => EditCountyTourismAttraction::route('/{record}/edit'),
        ];
    }
}
