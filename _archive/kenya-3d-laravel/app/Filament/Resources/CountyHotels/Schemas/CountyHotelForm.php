<?php

namespace App\Filament\Resources\CountyHotels\Schemas;

use App\Models\County;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CountyHotelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('county_id')
                    ->label('County')
                    ->options(County::pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('category')
                    ->options([
                        'hotel' => 'Hotel',
                        'lodge' => 'Lodge',
                        'resort' => 'Resort',
                        'guesthouse' => 'Guesthouse',
                    ])
                    ->required(),
                Select::make('star_rating')
                    ->options([
                        1 => '1 Star',
                        2 => '2 Stars',
                        3 => '3 Stars',
                        4 => '4 Stars',
                        5 => '5 Stars',
                    ])
                    ->nullable(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('location')
                    ->maxLength(255),
                TextInput::make('phone')
                    ->tel()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                TextInput::make('website')
                    ->url()
                    ->maxLength(255),
                TextInput::make('latitude')
                    ->numeric(),
                TextInput::make('longitude')
                    ->numeric(),
                TextInput::make('price_range_min')
                    ->numeric()
                    ->prefix('KES'),
                TextInput::make('price_range_max')
                    ->numeric()
                    ->prefix('KES'),
                KeyValue::make('amenities')
                    ->columnSpanFull(),
                Toggle::make('is_published')
                    ->required(),
            ]);
    }
}
