<?php

namespace App\Filament\Resources\Exhibitions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ExhibitionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('tagline')
                    ->default(null),
                TextInput::make('county_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('venue_id')
                    ->numeric()
                    ->default(null),
                DatePicker::make('start_date')
                    ->required(),
                DatePicker::make('end_date')
                    ->required(),
                TimePicker::make('open_time'),
                TimePicker::make('close_time'),
                FileUpload::make('cover_image')
                    ->image(),
                Textarea::make('gallery')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
                Toggle::make('is_featured')
                    ->required(),
                Textarea::make('organizer_info')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('meta')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
