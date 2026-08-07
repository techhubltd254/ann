<?php

namespace App\Filament\Resources\CountyHealthFacilities;

use App\Filament\Resources\CountyHealthFacilities\Pages\CreateCountyHealthFacility;
use App\Filament\Resources\CountyHealthFacilities\Pages\EditCountyHealthFacility;
use App\Filament\Resources\CountyHealthFacilities\Pages\ListCountyHealthFacilities;
use App\Filament\Resources\CountyHealthFacilities\Schemas\CountyHealthFacilityForm;
use App\Filament\Resources\CountyHealthFacilities\Tables\CountyHealthFacilitiesTable;
use App\Models\CountyHealthFacility;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CountyHealthFacilityResource extends Resource
{
    protected static ?string $model = CountyHealthFacility::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static string|UnitEnum|null $navigationGroup = 'County Data';

    public static function form(Schema $schema): Schema
    {
        return CountyHealthFacilityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CountyHealthFacilitiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCountyHealthFacilities::route('/'),
            'create' => CreateCountyHealthFacility::route('/create'),
            'edit' => EditCountyHealthFacility::route('/{record}/edit'),
        ];
    }
}
