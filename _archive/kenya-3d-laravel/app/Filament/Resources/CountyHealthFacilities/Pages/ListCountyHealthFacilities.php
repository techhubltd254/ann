<?php

namespace App\Filament\Resources\CountyHealthFacilities\Pages;

use App\Filament\Resources\CountyHealthFacilities\CountyHealthFacilityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCountyHealthFacilities extends ListRecords
{
    protected static string $resource = CountyHealthFacilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
