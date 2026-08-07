<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    public static function getNavigationGroup(): ?string { return 'System'; }

    public static function getNavigationSort(): ?int { return 1; }

    public static function getNavigationIcon(): string|\BackedEnum|null { return 'heroicon-o-users'; }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Section::make('Basic Information')->columns(2)->schema([
                    TextInput::make('name')->required(),
                    TextInput::make('email')->required()->email()->unique(ignoreRecord: true),
                    TextInput::make('phone')->tel(),
                    TextInput::make('password')
                        ->password()
                        ->required(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord)
                        ->hidden(fn ($livewire) => !$livewire instanceof \Filament\Resources\Pages\CreateRecord)
                        ->dehydrated(fn ($state) => filled($state)),
                ]),
                Section::make('Account Settings')->columns(2)->schema([
                    Select::make('account_type')
                        ->options(array_combine(User::$accountTypes, User::$accountTypes))
                        ->required(),
                    Select::make('county_id')->relationship('county', 'name'),
                    Select::make('status')
                        ->options([
                            'active' => 'Active',
                            'inactive' => 'Inactive',
                            'suspended' => 'Suspended',
                        ])
                        ->default('active'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->sortable(),
                TextColumn::make('account_type')->badge()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
            ])
            ->filters([
                Filter::make('status')->toggle(),
            ])
            ->actions([
                EditAction::make()->visible(fn () => Auth::user()?->can('edit_user')),
                DeleteAction::make()->visible(fn () => Auth::user()?->can('delete_user')),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->where('id', '!=', Auth::id()))
            ->defaultSort('created_at', 'desc');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->can('view_user') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}