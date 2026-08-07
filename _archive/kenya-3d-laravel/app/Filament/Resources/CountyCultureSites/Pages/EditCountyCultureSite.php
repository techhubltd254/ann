<?php

namespace App\Filament\Resources\CountyCultureSites\Pages;

use App\Filament\Resources\CountyCultureSites\CountyCultureSiteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCountyCultureSite extends EditRecord
{
    protected static string $resource = CountyCultureSiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
