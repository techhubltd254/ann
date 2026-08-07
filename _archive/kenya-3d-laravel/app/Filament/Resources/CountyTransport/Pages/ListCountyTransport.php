<?php

namespace App\Filament\Resources\CountyTransport\Pages;

use App\Filament\Resources\CountyTransport\CountyTransportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCountyTransport extends ListRecords
{
    protected static string $resource = CountyTransportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
