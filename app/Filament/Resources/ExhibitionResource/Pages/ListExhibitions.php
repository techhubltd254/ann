<?php

namespace App\Filament\Resources\ExhibitionResource\Pages;

use App\Filament\Resources\ExhibitionResource;
use Filament\Resources\Pages\ListRecords;

class ListExhibitions extends ListRecords
{
    protected static string $resource = ExhibitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()->visible(fn () => auth()->user()?->can('create_exhibition')),
        ];
    }
}