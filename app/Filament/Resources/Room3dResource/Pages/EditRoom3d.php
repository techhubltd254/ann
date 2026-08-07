<?php

namespace App\Filament\Resources\Room3dResource\Pages;

use App\Filament\Resources\Room3dResource;
use App\Models\Room3d;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Artisan;

class EditRoom3d extends EditRecord
{
    protected static string $resource = Room3dResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('process')
                ->action('processRoom')
                ->color('success')
                ->icon('heroicon-o-play')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === 'draft'),
            Actions\DeleteAction::make(),
        ];
    }

    public function processRoom(): void
    {
        $room = $this->record;
        $room->update(['status' => 'processing']);

        try {
            Artisan::call('room3d:process', ['id' => $room->id]);
            $room->refresh();
            $this->notify('success', 'Room processed: ' . $room->title);
        } catch (\Exception $e) {
            $room->update(['status' => 'failed']);
            $this->notify('danger', 'Processing failed: ' . $e->getMessage());
        }
    }
}