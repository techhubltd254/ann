<?php

namespace App\Filament\Resources\ImmersiveContentRecords;

use App\Filament\Resources\ImmersiveContentRecords\Pages\CreateImmersiveContentRecord;
use App\Filament\Resources\ImmersiveContentRecords\Pages\EditImmersiveContentRecord;
use App\Filament\Resources\ImmersiveContentRecords\Pages\ListImmersiveContentRecords;
use App\Filament\Resources\ImmersiveContentRecords\Schemas\ImmersiveContentRecordForm;
use App\Filament\Resources\ImmersiveContentRecords\Tables\ImmersiveContentRecordsTable;
use App\Models\ImmersiveContentRecord;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ImmersiveContentRecordResource extends Resource
{
    protected static ?string $model = ImmersiveContentRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlayCircle;

    protected static string|UnitEnum|null $navigationGroup = 'County Data';

    public static function form(Schema $schema): Schema
    {
        return ImmersiveContentRecordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ImmersiveContentRecordsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImmersiveContentRecords::route('/'),
            'create' => CreateImmersiveContentRecord::route('/create'),
            'edit' => EditImmersiveContentRecord::route('/{record}/edit'),
        ];
    }
}
