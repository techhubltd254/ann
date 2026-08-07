<?php

namespace App\Filament\Resources\TicketTypes\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TicketTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('exhibition_id')
                    ->required()
                    ->numeric(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->default(null),
                Textarea::make('description')
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
                TextInput::make('currency')
                    ->required()
                    ->default('KES'),
                TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('sold')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('max_per_order')
                    ->required()
                    ->numeric()
                    ->default(10),
                DateTimePicker::make('sale_start'),
                DateTimePicker::make('sale_end'),
                Textarea::make('benefits')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('color')
                    ->default(null),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
