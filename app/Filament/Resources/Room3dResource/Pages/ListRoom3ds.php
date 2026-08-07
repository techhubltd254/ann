<?php

namespace App\Filament\Resources\Room3dResource\Pages;

use App\Filament\Resources\Room3dResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRoom3ds extends ListRecords
{
    protected static string $resource = Room3dResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}