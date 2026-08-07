<?php

namespace App\Filament\Resources\Marketplace\ProductResource\Pages;

use App\Filament\Resources\Marketplace\ProductResource;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()->visible(fn () => auth()->user()?->can('create_product')),
        ];
    }
}