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
                            'hotel' => 'Hotel/Resort Venue',
                            'event_space' => 'Event Space',
                        ]),
                    Select::make('institution_id')
                        ->label('Institution/Hotel')
                        ->options(\App\Models\CountyInstitution::pluck('name', 'id'))
                        ->searchable()
                        ->nullable(),
                    Select::make('county')
                        ->label('County')
                        ->options(\App\Models\County::pluck('name', 'id'))
                        ->searchable()
                        ->nullable(),
                    Select::make('pipeline_code')
                        ->label('Pipeline')
                        ->options([
                            'P2' => 'P2 - Real Estate / Venues',
                            'C1' => 'C1 - Tourism / Hospitality',
                            'A1' => 'A1 - Marketplace',
                            'C5' => 'C5 - Outdoor Events',
                        ])
                        ->nullable()
                        ->helperText('Pipeline that processes this venue\'s bookings'),
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
        $user = Auth::user();

        return $table
            ->modifyQueryUsing(function (Builder $query) use ($user) {
                // County admins see only venues in their county
                if ($user && $user->hasRole('county_admin') && $user->county_id) {
                    $query->where('county', $user->county_id)
                        ->orWhereHas('institution', fn ($q) => $q->where('county_id', $user->county_id));
                }
                // Institution admins see only their venues
                if ($user && $user->hasRole('institution_admin') && $user->institution_id) {
                    $query->where('institution_id', $user->institution_id);
                }
            })
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('institution.name')->label('Institution/Hotel')->searchable()->sortable(),
                TextColumn::make('venue_type')->badge()->sortable(),
                TextColumn::make('pipeline_code')->label('Pipeline')->badge()->color('warning'),
                TextColumn::make('capacity')->numeric()->sortable(),
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