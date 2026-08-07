<?php

namespace App\Filament\Resources\CountyProducts\Pages;

use App\Filament\Resources\CountyProducts\CountyProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCountyProduct extends CreateRecord
{
    protected static string $resource = CountyProductResource::class;
}
