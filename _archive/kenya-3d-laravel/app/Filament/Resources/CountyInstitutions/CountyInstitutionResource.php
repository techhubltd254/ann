<?php

namespace App\Filament\Resources\CountyInstitutions;

use App\Filament\Resources\CountyInstitutions\Pages\CreateCountyInstitution;
use App\Filament\Resources\CountyInstitutions\Pages\EditCountyInstitution;
use App\Filament\Resources\CountyInstitutions\Pages\ListCountyInstitutions;
use App\Filament\Resources\CountyInstitutions\Schemas\CountyInstitutionForm;
use App\Filament\Resources\CountyInstitutions\Tables\CountyInstitutionsTable;
use App\Models\CountyInstitution;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CountyInstitutionResource extends Resource
{
    protected static ?string $model = CountyInstitution::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'County Data';

    public static function form(Schema $schema): Schema
    {
        return CountyInstitutionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CountyInstitutionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCountyInstitutions::route('/'),
            'create' => CreateCountyInstitution::route('/create'),
            'edit' => EditCountyInstitution::route('/{record}/edit'),
        ];
    }
}
