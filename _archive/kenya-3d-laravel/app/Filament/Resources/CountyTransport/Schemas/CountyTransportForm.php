<?php

namespace App\Filament\Resources\CountyTransport\Schemas;

use App\Models\County;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CountyTransportForm
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
                Select::make('type')
                    ->options([
                        'bus_station' => 'Bus Station',
                        'taxi_hub' => 'Taxi Hub',
                        'airstrip' => 'Airstrip',
                        'port' => 'Port',
                        'railway' => 'Railway',
                    ])
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('location')
                    ->maxLength(255),
                TextInput::make('operator')
                    ->maxLength(255),
                TextInput::make('contact')
                    ->maxLength(255),
                Toggle::make('is_published')
                    ->required(),
            ]);
    }
}
