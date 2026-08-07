<?php

namespace App\Filament\Resources\Marketplace\ProductResource\Pages;

use App\Filament\Resources\Marketplace\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->visible(fn () => auth()->user()?->can('delete_product')),
        ];
    }
}