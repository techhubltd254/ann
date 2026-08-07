<?php

namespace App\Filament\Resources\CountyTourismAttractions\Pages;

use App\Filament\Resources\CountyTourismAttractions\CountyTourismAttractionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCountyTourismAttractions extends ListRecords
{
    protected static string $resource = CountyTourismAttractionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
