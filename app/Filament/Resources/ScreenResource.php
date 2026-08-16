<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScreenResource\Pages;
use App\Models\Screen;
use Filament\Forms\Components\FileUpload;
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

class ScreenResource extends Resource
{
    protected static ?string $model = Screen::class;

    public static function getNavigationGroup(): ?string { return 'Content'; }

    public static function getNavigationSort(): ?int { return 5; }

    public static function getNavigationIcon(): string|\BackedEnum|null { return 'heroicon-o-tv'; }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Section::make('Basic Information')->columns(2)->schema([
                    TextInput::make('label')->required()->label('Name'),
                    TextInput::make('id')->required()->label('Screen ID'),
                    TextInput::make('location')->required(),
                    TextInput::make('target_duration_sec')->numeric()->integer()->label('Duration (s)'),
                    TextInput::make('refresh_interval_min')->numeric()->integer()->label('Refresh Interval (min)'),
                ]),
                Section::make('Media & Settings')->schema([
                    Textarea::make('description'),
                    FileUpload::make('image')
                        ->disk('public')
                        ->directory('screens')
                        ->image(),
                    Toggle::make('active')->label('Is Active')->default(true),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')->searchable()->sortable()->label('Name'),
                TextColumn::make('location')->searchable()->sortable(),
                TextColumn::make('target_duration_sec')->numeric()->sortable()->label('Duration'),
                IconColumn::make('active')->boolean()->sortable()->label('Active'),
            ])
            ->filters([
                Filter::make('active')
                    ->toggle()
                    ->query(fn (Builder $q) => $q->where('active', true)),
            ])
            ->actions([
                EditAction::make()->visible(fn () => Auth::user()?->can('edit_screen')),
                DeleteAction::make()->visible(fn () => Auth::user()?->can('delete_screen')),
            ])
            ->defaultSort('label');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScreens::route('/'),
            'create' => Pages\CreateScreen::route('/create'),
            'edit' => Pages\EditScreen::route('/{record}/edit'),
        ];
    }
}