<?php

namespace App\Filament\Resources\CountyHealthFacilities\Pages;

use App\Filament\Resources\CountyHealthFacilities\CountyHealthFacilityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCountyHealthFacility extends EditRecord
{
    protected static string $resource = CountyHealthFacilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
