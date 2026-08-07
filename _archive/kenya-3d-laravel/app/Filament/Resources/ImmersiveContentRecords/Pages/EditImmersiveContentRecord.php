<?php

namespace App\Filament\Resources\ImmersiveContentRecords\Pages;

use App\Filament\Resources\ImmersiveContentRecords\ImmersiveContentRecordResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditImmersiveContentRecord extends EditRecord
{
    protected static string $resource = ImmersiveContentRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
