<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    public static function getNavigationGroup(): ?string
    {
        return 'Commerce';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-receipt-percent';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Section::make('Booking Information')->columns(2)->schema([
                    TextInput::make('booking_reference')->label('Booking Number')->disabled(),
                    Select::make('user_id')->relationship('user', 'name')->disabled(),
                    Select::make('exhibition_id')->relationship('exhibition', 'name')->disabled(),
                    TextInput::make('total')->numeric()->prefix('KES')->disabled(),
                ]),
                Section::make('Status Management')->columns(2)->schema([
                    Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'confirmed' => 'Confirmed',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                            'refunded' => 'Refunded',
                        ])
                        ->required(),
                    Select::make('payment_status')
                        ->options([
                            'pending' => 'Pending',
                            'paid' => 'Paid',
                            'partial' => 'Partial',
                            'refunded' => 'Refunded',
                            'failed' => 'Failed',
                        ]),
                    Textarea::make('notes')->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking_reference')->searchable()->label('Booking #'),
                TextColumn::make('user.name')->searchable()->sortable(),
                TextColumn::make('exhibition.name')->searchable()->sortable(),
                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'info' => 'completed',
                        'danger' => 'cancelled',
                        'gray' => 'refunded',
                    ])
                    ->sortable(),
                TextColumn::make('total')->money('KES')->sortable(),
                BadgeColumn::make('payment_status')->sortable(),
            ])
            ->filters([
                Filter::make('status')->toggle(),
            ])
            ->actions([
                EditAction::make()->visible(fn () => Auth::user()?->can('edit_booking')),
            ])
            ->modifyQueryUsing(fn (Builder $query) => static::scopeQuery($query))
            ->defaultSort('created_at', 'desc');
    }

    public static function scopeQuery(Builder $query): Builder
    {
        $user = Auth::user();
        if ($user?->hasRole('county_admin')) {
            return $query->whereHas('exhibition', fn ($q) => $q->where('county_id', $user->county_id));
        }
        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}