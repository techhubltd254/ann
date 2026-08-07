<?php

namespace App\Filament\Resources\CountyFarms\Schemas;

use App\Models\County;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CountyFarmForm
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
                TextInput::make('type')
                    ->required()
                    ->maxLength(50),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('location')
                    ->maxLength(255),
                TextInput::make('contact')
                    ->maxLength(255),
                TextInput::make('size_acres')
                    ->numeric(),
                TextInput::make('main_crops')
                    ->maxLength(255),
                TextInput::make('products')
                    ->maxLength(255),
                Toggle::make('is_published')
                    ->required(),
            ]);
    }
}
