<?php

namespace App\Filament\Resources\CountyHealthFacilities\Schemas;

use App\Models\County;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CountyHealthFacilityForm
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
                Select::make('level')
                    ->options([
                        'Level 2' => 'Level 2',
                        'Level 3' => 'Level 3',
                        'Level 4' => 'Level 4',
                        'Level 5' => 'Level 5',
                        'Level 6' => 'Level 6',
                    ])
                    ->required(),
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
                TextInput::make('services')
                    ->maxLength(255),
                Toggle::make('is_published')
                    ->required(),
            ]);
    }
}
