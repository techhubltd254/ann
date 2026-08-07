<?php

namespace App\Filament\Resources\Marketplace;

use App\Filament\Resources\Marketplace\ProductResource\Pages;
use App\Models\County;
use App\Models\Marketplace\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    public static function getNavigationGroup(): ?string { return 'Commerce'; }

    public static function getNavigationSort(): ?int { return 1; }

    public static function getNavigationIcon(): string|\BackedEnum|null { return 'heroicon-o-shopping-bag'; }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Section::make('Basic Information')->columns(2)->schema([
                    TextInput::make('name')->required()->live(onBlur: true)
                        ->afterStateUpdated(fn ($set, $state) => $set('slug', Str::slug($state))),
                    TextInput::make('slug')->required()->unique(ignoreRecord: true),
                    Textarea::make('description')->columnSpanFull(),
                    TextInput::make('price')->numeric()->prefix('KES'),
                    TextInput::make('compare_price')->numeric()->prefix('KES')->label('Compare Price'),
                    Select::make('category_id')->relationship('category', 'name'),
                    Select::make('county_id')->relationship('county', 'name')
                        ->default(fn () => Auth::user()?->hasRole('county_admin') ? Auth::user()?->county_id : null)
                        ->visible(fn () => !Auth::user()?->hasRole('county_admin')),
                    Select::make('user_id')->relationship('seller', 'name')->label('Seller'),
                    TextInput::make('stock')->numeric()->default(0),
                    Toggle::make('is_active')->default(true),
                ]),
                Section::make('Images')->schema([
                    FileUpload::make('images')
                        ->disk('public')
                        ->directory('products')
                        ->multiple()
                        ->image()
                        ->maxFiles(10),
                ]),
                Section::make('Variants')->schema([
                    Repeater::make('variants')
                        ->relationship()
                        ->schema([
                            TextInput::make('name')->required(),
                            TextInput::make('price')->numeric()->required(),
                            TextInput::make('stock')->numeric()->default(0),
                            TextInput::make('sku')->label('SKU'),
                        ])->columns(2),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('county.name')->searchable()->sortable(),
                TextColumn::make('price')->money('KES')->sortable(),
                TextColumn::make('stock')->numeric()->sortable(),
                IconColumn::make('is_active')->boolean()->sortable(),
            ])
            ->filters([
                Filter::make('is_active')
                    ->toggle()
                    ->query(fn (Builder $q) => $q->where('is_active', true)),
            ])
            ->actions([
                EditAction::make()->visible(fn () => Auth::user()?->can('edit_product')),
                DeleteAction::make()->visible(fn () => Auth::user()?->can('delete_product')),
            ])
            ->modifyQueryUsing(fn (Builder $query) => static::scopeQuery($query))
            ->defaultSort('created_at', 'desc');
    }

    public static function scopeQuery(Builder $query): Builder
    {
        $user = Auth::user();
        if ($user?->hasRole('county_admin')) {
            return $query->where('county_id', $user->county_id);
        }
        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}