<?php

namespace App\Filament\Resources\CountyFarms\Pages;

use App\Filament\Resources\CountyFarms\CountyFarmResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCountyFarm extends EditRecord
{
    protected static string $resource = CountyFarmResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
