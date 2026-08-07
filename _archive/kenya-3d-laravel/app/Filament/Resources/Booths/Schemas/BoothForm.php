<?php

namespace App\Filament\Resources\Booths\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class BoothForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('exhibition_id')
                    ->required()
                    ->numeric(),
                TextInput::make('booth_number')
                    ->required(),
                TextInput::make('name')
                    ->default(null),
                TextInput::make('size')
                    ->required()
                    ->default('standard'),
                TextInput::make('category')
                    ->required()
                    ->default('standard'),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('amenities')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('discount_price')
                    ->numeric()
                    ->default(null)
                    ->prefix('$'),
                TextInput::make('max_quantity')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('booked_quantity')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('location_hint')
                    ->default(null),
                Textarea::make('dimensions')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('images')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('available'),
            ]);
    }
}
