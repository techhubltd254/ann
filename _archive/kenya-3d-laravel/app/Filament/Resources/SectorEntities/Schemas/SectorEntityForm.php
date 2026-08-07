<?php

namespace App\Filament\Resources\SectorEntities\Schemas;

use App\Models\County;
use App\Models\Sector;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SectorEntityForm
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
                Select::make('sector_id')
                    ->label('Sector')
                    ->options(Sector::pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('sector_type')
                    ->required()
                    ->maxLength(50),
                Select::make('capture_status')
                    ->options([
                        'none' => 'None',
                        'partial' => 'Partial',
                        'complete' => 'Complete',
                    ])
                    ->default('none'),
                TextInput::make('sponsor_funder_tag')
                    ->maxLength(255),
                TextInput::make('latitude')
                    ->numeric(),
                TextInput::make('longitude')
                    ->numeric(),
                KeyValue::make('contact_info')
                    ->columnSpanFull(),
                KeyValue::make('social_links')
                    ->columnSpanFull(),
                Toggle::make('is_published'),
                TextInput::make('language_primary')
                    ->maxLength(5)
                    ->default('en'),
                TagsInput::make('tags')
                    ->separator(','),
                TextInput::make('verification_owner')
                    ->maxLength(255),
                DateTimePicker::make('verification_date'),
            ]);
    }
}
