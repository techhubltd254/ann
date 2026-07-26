<?php

namespace App\Filament\Resources\Core;

use App\Filament\Resources\Core\CountyResource\Pages;
use App\Models\County;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
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
use Illuminate\Support\Str;

class CountyResource extends Resource
{
    protected static ?string $model = County::class;

    public static function getNavigationGroup(): ?string { return 'Content'; }

    public static function getNavigationSort(): ?int { return 1; }

    public static function getNavigationIcon(): string|\BackedEnum|null { return 'heroicon-o-map'; }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Section::make('Basic Information')->columns(2)->schema([
                    TextInput::make('name')->required()->live(onBlur: true)
                        ->afterStateUpdated(fn ($set, $state) => $set('slug', Str::slug($state))),
                    TextInput::make('slug')->required()->unique(ignoreRecord: true),
                    TextInput::make('region')->label('Region'),
                    TextInput::make('tagline')->columnSpanFull(),
                ]),
                Section::make('Details')->columns(2)->schema([
                    Textarea::make('description')->columnSpanFull(),
                    FileUpload::make('cover_image')
                        ->disk('public')
                        ->directory('counties')
                        ->image(),
                    Grid::make(2)->schema([
                        KeyValue::make('map_position')
                            ->keyLabel('Field')
                            ->valueLabel('Value'),
                        KeyValue::make('stats')
                            ->keyLabel('Stat')
                            ->valueLabel('Value'),
                    ])->columnSpanFull(),
                    Toggle::make('is_active')->default(true),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('region')->searchable(),
                IconColumn::make('is_active')->boolean()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('is_active')
                    ->toggle()
                    ->query(fn (Builder $q) => $q->where('is_active', true)),
            ])
            ->actions([
                EditAction::make()->visible(fn () => Auth::user()?->can('edit_county')),
                DeleteAction::make()->visible(fn () => Auth::user()?->can('delete_county')),
            ])
            ->modifyQueryUsing(fn (Builder $query) => static::scopeQuery($query))
            ->defaultSort('name');
    }

    public static function scopeQuery(Builder $query): Builder
    {
        $user = Auth::user();
        if ($user?->hasRole('county_admin')) {
            return $query->where('id', $user->county_id);
        }
        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCounties::route('/'),
            'create' => Pages\CreateCounty::route('/create'),
            'edit' => Pages\EditCounty::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Counties';
    }
}