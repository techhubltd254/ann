<?php

namespace App\Filament\Resources\CountyProducts\Schemas;

use App\Models\County;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CountyProductForm
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
                Select::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->nullable()
                    ->searchable(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('category')
                    ->required()
                    ->maxLength(50),
                TextInput::make('price')
                    ->numeric()
                    ->prefix('KES'),
                TextInput::make('unit')
                    ->maxLength(255),
                Select::make('status')
                    ->options([
                        'available' => 'Available',
                        'out_of_stock' => 'Out of Stock',
                        'discontinued' => 'Discontinued',
                    ])
                    ->default('available'),
                Toggle::make('is_published')
                    ->required(),
            ]);
    }
}
