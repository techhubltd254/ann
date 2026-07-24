<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Room3dResource\Pages;
use App\Models\Room3d;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class Room3dResource extends Resource
{
    protected static ?string $model = Room3d::class;
    protected static ?string $navigationLabel = '3D Experiences';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('slug'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoom3ds::route('/'),
            'edit' => Pages\EditRoom3d::route('/{record}/edit'),
        ];
    }
}