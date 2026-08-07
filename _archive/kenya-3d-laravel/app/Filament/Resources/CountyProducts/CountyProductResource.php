<?php

namespace App\Filament\Resources\CountyProducts;

use App\Filament\Resources\CountyProducts\Pages\CreateCountyProduct;
use App\Filament\Resources\CountyProducts\Pages\EditCountyProduct;
use App\Filament\Resources\CountyProducts\Pages\ListCountyProducts;
use App\Filament\Resources\CountyProducts\Schemas\CountyProductForm;
use App\Filament\Resources\CountyProducts\Tables\CountyProductsTable;
use App\Models\CountyProduct;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CountyProductResource extends Resource
{
    protected static ?string $model = CountyProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'County Data';

    public static function form(Schema $schema): Schema
    {
        return CountyProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CountyProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCountyProducts::route('/'),
            'create' => CreateCountyProduct::route('/create'),
            'edit' => EditCountyProduct::route('/{record}/edit'),
        ];
    }
}
