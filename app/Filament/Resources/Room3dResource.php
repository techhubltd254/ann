<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Room3dResource\Pages;
use App\Models\Room3d;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Room3dResource extends Resource
{
    protected static ?string $model = Room3d::class;

    public static function getNavigationGroup(): ?string { return 'System'; }

    public static function getNavigationSort(): ?int { return 2; }

    public static function getNavigationIcon(): string|\BackedEnum|null { return 'heroicon-o-cube'; }

    public static function getNavigationLabel(): string { return '3D Experiences'; }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Section::make('Basic Information')->columns(2)->schema([
                    TextInput::make('title')->required()->live(onBlur: true)
                        ->afterStateUpdated(fn ($set, $state) => $set('slug', Str::slug($state) . '-' . Str::random(6))),
                    TextInput::make('slug')->required()->unique(ignoreRecord: true),
                    Select::make('user_id')->relationship('user', 'name'),
                    Textarea::make('description')->columnSpanFull(),
                ]),
                Section::make('Media & Processing')->schema([
                    FileUpload::make('cover_image')
                        ->disk('public')
                        ->directory('room3d')
                        ->image(),
                    FileUpload::make('image_paths')
                        ->disk('public')
                        ->directory('room3d')
                        ->multiple()
                        ->image()
                        ->label('Images'),
                    Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'processing' => 'Processing',
                            'ready' => 'Ready',
                            'failed' => 'Failed',
                        ])
                        ->default('draft')
                        ->required(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->sortable()->searchable(),
                TextColumn::make('slug')->searchable()->toggleable(isToggledHiddenByDefault: true),
                BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'processing',
                        'success' => 'ready',
                        'danger' => 'failed',
                    ])
                    ->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Filter::make('status')->toggle(),
            ])
            ->actions([
                EditAction::make()->visible(fn () => Auth::user()?->can('edit_room3d')),
                DeleteAction::make()->visible(fn () => Auth::user()?->can('delete_room3d')),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoom3ds::route('/'),
            'create' => Pages\CreateRoom3d::route('/create'),
            'edit' => Pages\EditRoom3d::route('/{record}/edit'),
        ];
    }
}