<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VenueResource\Pages;
use App\Models\Venue;
use Filament\Forms\Components\CheckboxList;
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

class VenueResource extends Resource
{
    protected static ?string $model = Venue::class;

    public static function getNavigationGroup(): ?string { return 'Content'; }

    public static function getNavigationSort(): ?int { return 4; }

    public static function getNavigationIcon(): string|\BackedEnum|null { return 'heroicon-o-building-office'; }

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
                    TextInput::make('capacity')->numeric()->integer(),
                    TextInput::make('area')->label('Area (sqm)')->numeric(),
                    TextInput::make('price_day')->numeric()->prefix('KES')->label('Price per Day'),
                ]),
                Section::make('Media & Type')->columns(2)->schema([
                    FileUpload::make('cover_image')
                        ->disk('public')
                        ->directory('venues')
                        ->image(),
                    Select::make('venue_type')
                        ->options([
                            'indoor' => 'Indoor',
                            'outdoor' => 'Outdoor',
                            'hybrid' => 'Hybrid',
                            'conference' => 'Conference Hall',
                            'exhibition' => 'Exhibition Hall',
                        ]),
                    CheckboxList::make('amenities')
                        ->options([
                            'wifi' => 'WiFi',
                            'parking' => 'Parking',
                            'catering' => 'Catering',
                            'sound_system' => 'Sound System',
                            'projector' => 'Projector',
                            'air_conditioning' => 'Air Conditioning',
                            'stage' => 'Stage',
                            'backstage' => 'Backstage',
                        ])
                        ->columns(2),
                    Toggle::make('is_active')->default(true),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('capacity')->numeric()->sortable(),
                TextColumn::make('price_day')->money('KES')->sortable()->label('Price/Day'),
                TextColumn::make('venue_type')->badge()->sortable(),
                IconColumn::make('is_active')->boolean()->sortable(),
            ])
            ->filters([
                Filter::make('is_active')
                    ->toggle()
                    ->query(fn (Builder $q) => $q->where('is_active', true)),
            ])
            ->actions([
                EditAction::make()->visible(fn () => Auth::user()?->can('edit_venue')),
                DeleteAction::make()->visible(fn () => Auth::user()?->can('delete_venue')),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVenues::route('/'),
            'create' => Pages\CreateVenue::route('/create'),
            'edit' => Pages\EditVenue::route('/{record}/edit'),
        ];
    }
}