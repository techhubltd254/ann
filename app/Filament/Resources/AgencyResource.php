<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgencyResource\Pages;
use App\Models\Agency;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AgencyResource extends Resource
{
    protected static ?string $model = Agency::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Agency Details')->schema([
                Forms\Components\Select::make('ministry_id')
                    ->relationship('ministry', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(10)
                    ->unique(ignoreRecord: true),
                Forms\Components\FileUpload::make('logo')
                    ->image()
                    ->disk('public')
                    ->directory('kicc/agencies')
                    ->visibility('public'),
                Forms\Components\Textarea::make('description')->rows(3),
                Forms\Components\TextInput::make('website')->url(),
                Forms\Components\TextInput::make('contact_email')->email(),
                Forms\Components\Toggle::make('is_active')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->disk('public')
                    ->size(40)
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('ministry.name')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->dateTime('M d, Y')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('ministry')
                    ->relationship('ministry', 'name'),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn () => Auth::user()?->can('edit_agency') ?? false),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => Auth::user()?->can('delete_agency') ?? false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()?->can('delete_agency') ?? false),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgencies::route('/'),
            'create' => Pages\CreateAgency::route('/create'),
            'edit' => Pages\EditAgency::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->can('view_agency') ?? false;
    }
}
