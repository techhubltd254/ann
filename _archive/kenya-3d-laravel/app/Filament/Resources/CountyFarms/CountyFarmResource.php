<?php

namespace App\Filament\Resources\CountyFarms;

use App\Filament\Resources\CountyFarms\Pages\CreateCountyFarm;
use App\Filament\Resources\CountyFarms\Pages\EditCountyFarm;
use App\Filament\Resources\CountyFarms\Pages\ListCountyFarms;
use App\Filament\Resources\CountyFarms\Schemas\CountyFarmForm;
use App\Filament\Resources\CountyFarms\Tables\CountyFarmsTable;
use App\Models\CountyFarm;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CountyFarmResource extends Resource
{
    protected static ?string $model = CountyFarm::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|UnitEnum|null $navigationGroup = 'County Data';

    public static function form(Schema $schema): Schema
    {
        return CountyFarmForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CountyFarmsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCountyFarms::route('/'),
            'create' => CreateCountyFarm::route('/create'),
            'edit' => EditCountyFarm::route('/{record}/edit'),
        ];
    }
}
