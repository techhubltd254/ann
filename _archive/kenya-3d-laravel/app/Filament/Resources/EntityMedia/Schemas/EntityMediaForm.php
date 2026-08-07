<?php

namespace App\Filament\Resources\EntityMedia\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\KeyValue;
use Filament\Schemas\Schema;

class EntityMediaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                MorphToSelect::make('mediable')
                    ->types([
                        MorphToSelect\Type::make(\App\Models\SectorEntity::class)
                            ->titleAttribute('name'),
                        MorphToSelect\Type::make(\App\Models\CountyTourismAttraction::class)
                            ->titleAttribute('name'),
                        MorphToSelect\Type::make(\App\Models\CountyHotel::class)
                            ->titleAttribute('name'),
                        MorphToSelect\Type::make(\App\Models\CountyProduct::class)
                            ->titleAttribute('name'),
                        MorphToSelect\Type::make(\App\Models\CountyInstitution::class)
                            ->titleAttribute('name'),
                        MorphToSelect\Type::make(\App\Models\CountyFarm::class)
                            ->titleAttribute('name'),
                        MorphToSelect\Type::make(\App\Models\CountyTransport::class)
                            ->titleAttribute('name'),
                        MorphToSelect\Type::make(\App\Models\CountyHealthFacility::class)
                            ->titleAttribute('name'),
                        MorphToSelect\Type::make(\App\Models\CountyCultureSite::class)
                            ->titleAttribute('name'),
                    ])
                    ->required(),
                Select::make('type')
                    ->options([
                        'image' => 'Image',
                        'video' => 'Video',
                        'document' => 'Document',
                        '3d_model' => '3D Model',
                    ])
                    ->required(),
                FileUpload::make('url')
                    ->disk('public')
                    ->directory('media')
                    ->visibility('public')
                    ->required(),
                FileUpload::make('thumbnail_url')
                    ->image()
                    ->disk('public')
                    ->directory('media/thumbnails')
                    ->visibility('public'),
                TextInput::make('alt_text')
                    ->maxLength(255),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
                KeyValue::make('metadata')
                    ->columnSpanFull(),
            ]);
    }
}
