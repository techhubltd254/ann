<?php

namespace App\Filament\Resources\CountyProducts\Pages;

use App\Filament\Resources\CountyProducts\CountyProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCountyProducts extends ListRecords
{
    protected static string $resource = CountyProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
