<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionPlanResource\Pages;
use App\Models\SubscriptionPlan;
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

class SubscriptionPlanResource extends Resource
{
    protected static ?string $model = SubscriptionPlan::class;

    public static function getNavigationGroup(): ?string { return 'Commerce'; }

    public static function getNavigationSort(): ?int { return 3; }

    public static function getNavigationIcon(): string|\BackedEnum|null { return 'heroicon-o-credit-card'; }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Section::make('Plan Details')->columns(2)->schema([
                    TextInput::make('name')->required(),
                    TextInput::make('slug')->required()->unique(ignoreRecord: true),
                    Textarea::make('description')->columnSpanFull(),
                    TextInput::make('price')->numeric()->required()->prefix('KES'),
                    Select::make('billing_interval')->label('Period')
                        ->options([
                            'monthly' => 'Monthly',
                            'quarterly' => 'Quarterly',
                            'yearly' => 'Yearly',
                        ])
                        ->required(),
                    Textarea::make('features')->columnSpanFull(),
                ]),
                Section::make('Settings')->schema([
                    Select::make('county_id')
                        ->relationship('county', 'name')
                        ->nullable()
                        ->label('County (leave empty for global)'),
                    Toggle::make('is_active')->default(true),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('price')->money('KES')->sortable(),
                TextColumn::make('billing_interval')->badge()->sortable()->label('Period'),
                IconColumn::make('is_active')->boolean()->sortable(),
            ])
            ->filters([
                Filter::make('is_active')
                    ->toggle()
                    ->query(fn (Builder $q) => $q->where('is_active', true)),
            ])
            ->actions([
                EditAction::make()->visible(fn () => Auth::user()?->can('edit_subscription')),
                DeleteAction::make(),
            ])
            ->defaultSort('price');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptionPlans::route('/'),
            'create' => Pages\CreateSubscriptionPlan::route('/create'),
            'edit' => Pages\EditSubscriptionPlan::route('/{record}/edit'),
        ];
    }
}