<?php

namespace App\Filament\Resources\CountyProducts\Pages;

use App\Filament\Resources\CountyProducts\CountyProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCountyProduct extends EditRecord
{
    protected static string $resource = CountyProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
