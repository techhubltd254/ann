<?php

namespace App\Filament\Resources\Core\CountyResource\Pages;

use App\Filament\Resources\Core\CountyResource;
use Filament\Resources\Pages\ListRecords;

class ListCounties extends ListRecords
{
    protected static string $resource = CountyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()->visible(fn () => auth()->user()?->can('create_county')),
        ];
    }
}