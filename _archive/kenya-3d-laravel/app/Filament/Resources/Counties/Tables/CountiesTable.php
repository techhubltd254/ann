<?php

namespace App\Filament\Resources\Counties\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CountiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('capital')
                    ->searchable(),
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('former_province')
                    ->searchable(),
                TextColumn::make('economic_zone')
                    ->searchable(),
                TextColumn::make('population_2024')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('area_km2')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('latitude')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('longitude')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('weather_station_id')
                    ->searchable(),
                TextColumn::make('icon_emoji')
                    ->searchable(),
                \Filament\Tables\Columns\ImageColumn::make('profile_image')
                    ->disk('public')
                    ->size(40)
                    ->circular(),
                TextColumn::make('tagline')
                    ->searchable(),
                TextColumn::make('warmest_month')
                    ->searchable(),
                TextColumn::make('coolest_month')
                    ->searchable(),
                TextColumn::make('rainy_season')
                    ->searchable(),
                TextColumn::make('dry_season')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
