<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExhibitionResource\Pages;
use App\Models\Exhibition;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
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

class ExhibitionResource extends Resource
{
    protected static ?string $model = Exhibition::class;

    public static function getNavigationGroup(): ?string { return 'Content'; }

    public static function getNavigationSort(): ?int { return 3; }

    public static function getNavigationIcon(): string|\BackedEnum|null { return 'heroicon-o-calendar-days'; }

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
                    Select::make('venue_id')->relationship('venue', 'name'),
                    DateTimePicker::make('start_date')->required(),
                    DateTimePicker::make('end_date')->required(),
                ]),
                Section::make('Settings')->columns(2)->schema([
                    FileUpload::make('cover_image')
                        ->disk('public')
                        ->directory('exhibitions')
                        ->image(),
                    TextInput::make('booth_price')->numeric()->prefix('KES'),
                    TextInput::make('max_booths')->numeric()->integer(),
                    Toggle::make('is_active')->default(true),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('venue.name')->searchable()->sortable(),
                TextColumn::make('start_date')->date()->sortable(),
                TextColumn::make('end_date')->date()->sortable(),
                IconColumn::make('is_active')->boolean()->sortable(),
            ])
            ->filters([
                Filter::make('is_active')
                    ->toggle()
                    ->query(fn (Builder $q) => $q->where('is_active', true)),
            ])
            ->actions([
                EditAction::make()->visible(fn () => Auth::user()?->can('edit_exhibition')),
                DeleteAction::make()->visible(fn () => Auth::user()?->can('delete_exhibition')),
            ])
            ->defaultSort('start_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExhibitions::route('/'),
            'create' => Pages\CreateExhibition::route('/create'),
            'edit' => Pages\EditExhibition::route('/{record}/edit'),
        ];
    }
}