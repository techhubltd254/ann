<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('account_type')
                    ->default(null),
                TextInput::make('phone')
                    ->tel()
                    ->default(null),
                TextInput::make('county_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('id_number')
                    ->default(null),
                TextInput::make('kra_pin')
                    ->default(null),
                TextInput::make('business_reg')
                    ->default(null),
                DateTimePicker::make('email_verified_at'),
                DateTimePicker::make('phone_verified_at'),
                Toggle::make('mfa_enabled')
                    ->required(),
                Textarea::make('mfa_secret')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                Textarea::make('metadata')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('password')
                    ->password()
                    ->required(),
            ]);
    }
}
