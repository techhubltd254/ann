<?php

namespace App\Filament\Resources\CountyTourismAttractions\Pages;

use App\Filament\Resources\CountyTourismAttractions\CountyTourismAttractionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCountyTourismAttraction extends EditRecord
{
    protected static string $resource = CountyTourismAttractionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
