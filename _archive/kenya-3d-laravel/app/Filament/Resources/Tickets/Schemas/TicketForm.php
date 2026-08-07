<?php

namespace App\Filament\Resources\Tickets\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class TicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('ticket_code')
                    ->required(),
                TextInput::make('booking_id')
                    ->required()
                    ->numeric(),
                TextInput::make('ticket_type_id')
                    ->required()
                    ->numeric(),
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                TextInput::make('qr_code')
                    ->default(null),
                TextInput::make('holder_name')
                    ->default(null),
                TextInput::make('holder_email')
                    ->email()
                    ->default(null),
                Textarea::make('check_in_data')
                    ->default(null)
                    ->columnSpanFull(),
                DateTimePicker::make('checked_in_at'),
                DateTimePicker::make('cancelled_at'),
            ]);
    }
}
