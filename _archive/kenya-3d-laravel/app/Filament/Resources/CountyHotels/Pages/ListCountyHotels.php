<?php

namespace App\Filament\Resources\CountyHotels\Pages;

use App\Filament\Resources\CountyHotels\CountyHotelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCountyHotels extends ListRecords
{
    protected static string $resource = CountyHotelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
