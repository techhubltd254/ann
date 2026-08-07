<?php

namespace App\Filament\Resources\Counties\Pages;

use App\Filament\Resources\Counties\CountyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCounties extends ListRecords
{
    protected static string $resource = CountyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
