<?php

namespace App\Filament\Resources\CountyInstitutions\Pages;

use App\Filament\Resources\CountyInstitutions\CountyInstitutionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCountyInstitution extends EditRecord
{
    protected static string $resource = CountyInstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
