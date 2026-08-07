<?php

namespace App\Filament\Resources\ImmersiveContentRecords\Pages;

use App\Filament\Resources\ImmersiveContentRecords\ImmersiveContentRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListImmersiveContentRecords extends ListRecords
{
    protected static string $resource = ImmersiveContentRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
