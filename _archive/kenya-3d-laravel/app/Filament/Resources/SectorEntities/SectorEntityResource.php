<?php

namespace App\Filament\Resources\SectorEntities;

use App\Filament\Resources\SectorEntities\Pages\CreateSectorEntity;
use App\Filament\Resources\SectorEntities\Pages\EditSectorEntity;
use App\Filament\Resources\SectorEntities\Pages\ListSectorEntities;
use App\Filament\Resources\SectorEntities\Schemas\SectorEntityForm;
use App\Filament\Resources\SectorEntities\Tables\SectorEntitiesTable;
use App\Models\SectorEntity;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SectorEntityResource extends Resource
{
    protected static ?string $model = SectorEntity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'County Data';

    public static function form(Schema $schema): Schema
    {
        return SectorEntityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SectorEntitiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSectorEntities::route('/'),
            'create' => CreateSectorEntity::route('/create'),
            'edit' => EditSectorEntity::route('/{record}/edit'),
        ];
    }
}
