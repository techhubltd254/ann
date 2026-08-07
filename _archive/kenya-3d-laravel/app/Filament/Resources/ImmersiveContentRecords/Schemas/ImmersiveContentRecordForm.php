<?php

namespace App\Filament\Resources\ImmersiveContentRecords\Schemas;

use App\Models\County;
use App\Models\SectorEntity;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ImmersiveContentRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('sector_entity_id')
                    ->label('Sector Entity')
                    ->options(SectorEntity::pluck('name', 'id'))
                    ->nullable()
                    ->searchable(),
                Select::make('county_id')
                    ->label('County')
                    ->options(County::pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Select::make('tier')
                    ->options([
                        'A' => 'Tier A',
                        'B' => 'Tier B',
                        'C' => 'Tier C',
                    ])
                    ->required(),
                TextInput::make('content_type')
                    ->required()
                    ->maxLength(30),
                FileUpload::make('file_url')
                    ->disk('public')
                    ->directory('immersive')
                    ->visibility('public')
                    ->required(),
                FileUpload::make('preview_url')
                    ->image()
                    ->disk('public')
                    ->directory('immersive/previews')
                    ->visibility('public'),
                KeyValue::make('metadata')
                    ->columnSpanFull(),
                Toggle::make('is_published'),
            ]);
    }
}
