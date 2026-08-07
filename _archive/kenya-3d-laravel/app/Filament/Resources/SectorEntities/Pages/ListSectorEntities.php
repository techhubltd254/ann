<?php

namespace App\Filament\Resources\SectorEntities\Pages;

use App\Filament\Resources\SectorEntities\SectorEntityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSectorEntities extends ListRecords
{
    protected static string $resource = SectorEntityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
