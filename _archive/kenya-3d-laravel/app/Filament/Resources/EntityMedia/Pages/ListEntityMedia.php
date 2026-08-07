<?php

namespace App\Filament\Resources\EntityMedia\Pages;

use App\Filament\Resources\EntityMedia\EntityMediaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEntityMedia extends ListRecords
{
    protected static string $resource = EntityMediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
