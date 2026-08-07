<?php

namespace App\Filament\Resources\EntityMedia;

use App\Filament\Resources\EntityMedia\Pages\CreateEntityMedia;
use App\Filament\Resources\EntityMedia\Pages\EditEntityMedia;
use App\Filament\Resources\EntityMedia\Pages\ListEntityMedia;
use App\Filament\Resources\EntityMedia\Schemas\EntityMediaForm;
use App\Filament\Resources\EntityMedia\Tables\EntityMediaTable;
use App\Models\EntityMedia;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EntityMediaResource extends Resource
{
    protected static ?string $model = EntityMedia::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'County Data';

    public static function form(Schema $schema): Schema
    {
        return EntityMediaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EntityMediaTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEntityMedia::route('/'),
            'create' => CreateEntityMedia::route('/create'),
            'edit' => EditEntityMedia::route('/{record}/edit'),
        ];
    }
}
