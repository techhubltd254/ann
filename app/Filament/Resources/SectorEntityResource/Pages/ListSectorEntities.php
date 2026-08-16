<?php

namespace App\Filament\Resources\SectorEntityResource\Pages;

use App\Filament\Resources\SectorEntityResource;
use Filament\Resources\Pages\ListRecords;

class ListSectorEntities extends ListRecords
{
    protected static string $resource = SectorEntityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()->visible(fn () => auth()->user()?->can('create_sector_entity')),
        ];
    }
}