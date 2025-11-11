<?php

namespace App\Filament\Resources\SummaryResource\Pages;

use App\Filament\Resources\SummaryResource;
use App\Models\Summary;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSummaries extends ListRecords
{
    protected static string $resource = SummaryResource::class;

    public function mount(): void
    {
        parent::mount();
        
        // Pastikan data summary sudah ter-generate saat pertama kali dibuka
        if (Summary::count() === 0) {
            Summary::updateSummary();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            // Actions\Action::make('refresh')
            //     ->label('Refresh')
            //     ->action(function () {
            //         Summary::updateSummary();
            //     })
            //     ->requiresConfirmation()
            //     ->color('primary'),
        ];
    }
}
