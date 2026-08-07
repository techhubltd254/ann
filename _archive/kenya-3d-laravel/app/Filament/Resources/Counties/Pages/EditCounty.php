<?php

namespace App\Filament\Resources\Counties\Pages;

use App\Filament\Resources\Counties\CountyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCounty extends EditRecord
{
    protected static string $resource = CountyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
