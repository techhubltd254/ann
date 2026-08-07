<?php

namespace App\Filament\Resources\CountyCultureSites;

use App\Filament\Resources\CountyCultureSites\Pages\CreateCountyCultureSite;
use App\Filament\Resources\CountyCultureSites\Pages\EditCountyCultureSite;
use App\Filament\Resources\CountyCultureSites\Pages\ListCountyCultureSites;
use App\Filament\Resources\CountyCultureSites\Schemas\CountyCultureSiteForm;
use App\Filament\Resources\CountyCultureSites\Tables\CountyCultureSitesTable;
use App\Models\CountyCultureSite;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CountyCultureSiteResource extends Resource
{
    protected static ?string $model = CountyCultureSite::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMusicalNote;

    protected static string|UnitEnum|null $navigationGroup = 'County Data';

    public static function form(Schema $schema): Schema
    {
        return CountyCultureSiteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CountyCultureSitesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCountyCultureSites::route('/'),
            'create' => CreateCountyCultureSite::route('/create'),
            'edit' => EditCountyCultureSite::route('/{record}/edit'),
        ];
    }
}
