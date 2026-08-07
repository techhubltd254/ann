<?php

namespace App\Filament\Resources\CountyTransport\Pages;

use App\Filament\Resources\CountyTransport\CountyTransportResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCountyTransport extends EditRecord
{
    protected static string $resource = CountyTransportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
