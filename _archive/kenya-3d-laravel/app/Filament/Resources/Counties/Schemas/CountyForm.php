<?php

namespace App\Filament\Resources\Counties\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CountyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('capital')
                    ->required(),
                TextInput::make('code')
                    ->required(),
                TextInput::make('former_province')
                    ->required(),
                TextInput::make('economic_zone')
                    ->required(),
                TextInput::make('population_2024')
                    ->numeric()
                    ->default(null),
                TextInput::make('area_km2')
                    ->numeric()
                    ->default(null),
                TextInput::make('latitude')
                    ->numeric()
                    ->default(null),
                TextInput::make('longitude')
                    ->numeric()
                    ->default(null),
                TextInput::make('weather_station_id')
                    ->default(null),
                Textarea::make('primary_sectors')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('icon_emoji')
                    ->default(null),
                FileUpload::make('profile_image')
                    ->image()
                    ->disk('public')
                    ->directory('counties')
                    ->visibility('public')
                    ->imagePreviewHeight(100)
                    ->columnSpanFull(),
                TextInput::make('tagline')
                    ->default(null),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('tourism_highlights')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('warmest_month')
                    ->default(null),
                TextInput::make('coolest_month')
                    ->default(null),
                TextInput::make('rainy_season')
                    ->default(null),
                TextInput::make('dry_season')
                    ->default(null),
                TextInput::make('slug')
                    ->required(),
                Textarea::make('weather_tags')
                    ->default(null)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
