<?php

namespace App\Filament\Resources\Marketplace;

use App\Filament\Resources\Marketplace\ProductResource\Pages;
use App\Models\Marketplace\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?int $navigationSort = 10;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('county.name'),
                TextColumn::make('category.name'),
                TextColumn::make('sku'),
                TextColumn::make('variants_count')->counts('variants'),
                TextColumn::make('status'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListProducts::route('/'), 'edit' => Pages\EditProduct::route('/{record}/edit')];
    }
}
