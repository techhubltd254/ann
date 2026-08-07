<?php

namespace App\Filament\Resources\Core\CountyResource\Pages;

use App\Filament\Resources\Core\CountyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCounty extends EditRecord
{
    protected static string $resource = CountyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->visible(fn () => auth()->user()?->can('delete_county')),
        ];
    }
}