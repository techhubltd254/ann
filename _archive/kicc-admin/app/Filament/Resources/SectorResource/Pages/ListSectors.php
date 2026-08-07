<?php

namespace App\Filament\Resources\SectorResource\Pages;

use App\Filament\Resources\SectorResource;
use Filament\Resources\Pages\ListRecords;

class ListSectors extends ListRecords
{
    protected static string $resource = SectorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()->visible(fn () => auth()->user()?->can('create_sector')),
        ];
    }
}