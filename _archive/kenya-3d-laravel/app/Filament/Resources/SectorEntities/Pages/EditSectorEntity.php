<?php

namespace App\Filament\Resources\SectorEntities\Pages;

use App\Filament\Resources\SectorEntities\SectorEntityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSectorEntity extends EditRecord
{
    protected static string $resource = SectorEntityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
