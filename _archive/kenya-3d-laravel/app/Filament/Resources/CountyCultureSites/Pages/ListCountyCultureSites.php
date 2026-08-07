<?php

namespace App\Filament\Resources\CountyCultureSites\Pages;

use App\Filament\Resources\CountyCultureSites\CountyCultureSiteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCountyCultureSites extends ListRecords
{
    protected static string $resource = CountyCultureSiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
