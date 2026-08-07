<?php

namespace App\Filament\Resources\CountyHotels\Pages;

use App\Filament\Resources\CountyHotels\CountyHotelResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCountyHotel extends EditRecord
{
    protected static string $resource = CountyHotelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
