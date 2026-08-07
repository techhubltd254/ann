<?php

namespace App\Filament\Resources\Counties;

use App\Filament\Resources\Counties\Pages\CreateCounty;
use App\Filament\Resources\Counties\Pages\EditCounty;
use App\Filament\Resources\Counties\Pages\ListCounties;
use App\Filament\Resources\Counties\Schemas\CountyForm;
use App\Filament\Resources\Counties\Tables\CountiesTable;
use App\Models\County;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CountyResource extends Resource
{
    protected static ?string $model = County::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CountyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CountiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCounties::route('/'),
            'create' => CreateCounty::route('/create'),
            'edit' => EditCounty::route('/{record}/edit'),
        ];
    }
}
