<?php

namespace App\Filament\Resources\CountyTourismAttractions\Schemas;

use App\Models\County;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CountyTourismAttractionForm
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
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('category')
                    ->options([
                        'nature' => 'Nature',
                        'adventure' => 'Adventure',
                        'cultural' => 'Cultural',
                        'wildlife' => 'Wildlife',
                        'scenic' => 'Scenic',
                    ])
                    ->required(),
                TextInput::make('location')
                    ->maxLength(255),
                TextInput::make('entry_fee')
                    ->numeric()
                    ->prefix('KES'),
                TextInput::make('opening_hours')
                    ->maxLength(255),
                TextInput::make('contact')
                    ->maxLength(255),
                TextInput::make('latitude')
                    ->numeric(),
                TextInput::make('longitude')
                    ->numeric(),
                Toggle::make('is_published')
                    ->required(),
            ]);
    }
}
