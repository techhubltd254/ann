<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SectorEntityResource\Pages;
use App\Models\SectorEntity;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SectorEntityResource extends Resource
{
    protected static ?string $model = SectorEntity::class;

    public static function getNavigationGroup(): ?string { return 'Content'; }

    public static function getNavigationSort(): ?int { return 6; }

    public static function getNavigationIcon(): string|\BackedEnum|null { return 'heroicon-o-building-storefront'; }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Section::make('Basic Information')->columns(2)->schema([
                    TextInput::make('name')->required(),
                    TextInput::make('sector_type')->label('Type'),
                    Select::make('sector_id')->relationship('sector', 'name')->required(),
                    Select::make('county_id')->relationship('county', 'name')
                        ->default(fn () => Auth::user()?->hasRole('county_admin') ? Auth::user()?->county_id : null)
                        ->visible(fn () => !Auth::user()?->hasRole('county_admin')),
                    Textarea::make('description')->columnSpanFull(),
                ]),
                Section::make('Details')->columns(2)->schema([
                    TextInput::make('rating')->numeric()->minValue(0)->maxValue(5)->step(0.1),
                    TextInput::make('price')->numeric()->prefix('KES'),
                    Textarea::make('contact_info')->columnSpanFull(),
                    FileUpload::make('images')
                        ->disk('public')
                        ->directory('sector-entities')
                        ->multiple()
                        ->image(),
                    Toggle::make('is_published')->label('Is Active')->default(true),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('sector_type')->badge()->sortable()->label('Type'),
                TextColumn::make('sector.name')->searchable()->sortable(),
                TextColumn::make('county.name')->searchable()->sortable(),
                IconColumn::make('is_published')->boolean()->sortable()->label('Active'),
            ])
            ->filters([
                Filter::make('is_published')
                    ->toggle()
                    ->query(fn (Builder $q) => $q->where('is_published', true)),
            ])
            ->actions([
                EditAction::make()->visible(fn () => Auth::user()?->can('edit_sector_entity')),
                DeleteAction::make()->visible(fn () => Auth::user()?->can('delete_sector_entity')),
            ])
            ->modifyQueryUsing(fn (Builder $query) => static::scopeQuery($query))
            ->defaultSort('name');
    }

    public static function scopeQuery(Builder $query): Builder
    {
        $user = Auth::user();
        if ($user?->hasRole('county_admin')) {
            return $query->where('county_id', $user->county_id);
        }
        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSectorEntities::route('/'),
            'create' => Pages\CreateSectorEntity::route('/create'),
            'edit' => Pages\EditSectorEntity::route('/{record}/edit'),
        ];
    }
}