<?php

namespace App\Filament\Resources\CountyInstitutions\Pages;

use App\Filament\Resources\CountyInstitutions\CountyInstitutionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCountyInstitutions extends ListRecords
{
    protected static string $resource = CountyInstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
