<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SectorResource\Pages;
use App\Models\Sector;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SectorResource extends Resource
{
    protected static ?string $model = Sector::class;

    public static function getNavigationGroup(): ?string { return 'Content'; }

    public static function getNavigationSort(): ?int { return 2; }

    public static function getNavigationIcon(): string|\BackedEnum|null { return 'heroicon-o-tag'; }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Section::make('Basic Information')->columns(2)->schema([
                    TextInput::make('name')->required()->live(onBlur: true)
                        ->afterStateUpdated(fn ($set, $state) => $set('slug', Str::slug($state))),
                    TextInput::make('slug')->required()->unique(ignoreRecord: true),
                    TextInput::make('icon')->label('Icon (emoji)'),
                    Textarea::make('description')->columnSpanFull(),
                ]),
                Section::make('Media & Settings')->columns(2)->schema([
                    FileUpload::make('image')
                        ->disk('public')
                        ->directory('sectors')
                        ->image(),
                    Select::make('counties')
                        ->relationship('counties', 'name')
                        ->multiple()
                        ->preload(),
                    Toggle::make('is_active')->default(true),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('icon')->label('Icon'),
                TextColumn::make('counties_count')->counts('counties')->label('Counties')->sortable(),
                IconColumn::make('is_active')->boolean()->sortable(),
            ])
            ->actions([
                EditAction::make()->visible(fn () => Auth::user()?->can('edit_sector')),
                DeleteAction::make()->visible(fn () => Auth::user()?->can('delete_sector')),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSectors::route('/'),
            'create' => Pages\CreateSector::route('/create'),
            'edit' => Pages\EditSector::route('/{record}/edit'),
        ];
    }
}