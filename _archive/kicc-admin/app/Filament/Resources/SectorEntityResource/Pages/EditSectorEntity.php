<?php

namespace App\Filament\Resources\SectorEntityResource\Pages;

use App\Filament\Resources\SectorEntityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSectorEntity extends EditRecord
{
    protected static string $resource = SectorEntityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->visible(fn () => auth()->user()?->can('delete_sector_entity')),
        ];
    }
}