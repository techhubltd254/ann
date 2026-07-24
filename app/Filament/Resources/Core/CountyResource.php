<?php

namespace App\Filament\Resources\Core;

use App\Filament\Resources\Core\CountyResource\Pages;
use App\Models\County;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;

class CountyResource extends Resource
{
    protected static ?string $model = County::class;
    protected static ?int $navigationSort = 1;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Details')->columns(2)->schema([
                TextInput::make('name')->required(),
                TextInput::make('slug')->required(),
                TextInput::make('capital'),
                TextInput::make('code')->label('County Code'),
                TextInput::make('population_2024')->numeric(),
                TextInput::make('area_km2')->numeric(),
            ]),
            Section::make('Content')->schema([
                Textarea::make('tagline'),
                Textarea::make('description'),
                Textarea::make('tourism_highlights'),
                Textarea::make('primary_sectors'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('hero')->getStateUsing(fn ($r) => $r->hero_url ?? '')->size(40),
                TextColumn::make('code')->searchable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('capital'),
                TextColumn::make('population_2024')->numeric()->sortable(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListCounties::route('/'), 'edit' => Pages\EditCounty::route('/{record}/edit')];
    }
}
