<?php

namespace App\Filament\Resources\CountyFarms\Pages;

use App\Filament\Resources\CountyFarms\CountyFarmResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCountyFarms extends ListRecords
{
    protected static string $resource = CountyFarmResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
