<?php

namespace App\Filament\Resources\EntityMedia\Pages;

use App\Filament\Resources\EntityMedia\EntityMediaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEntityMedia extends EditRecord
{
    protected static string $resource = EntityMediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
