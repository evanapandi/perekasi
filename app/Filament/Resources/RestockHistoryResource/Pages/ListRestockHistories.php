<?php

namespace App\Filament\Resources\RestockHistoryResource\Pages;

use App\Filament\Resources\RestockHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\RestockHistory;

class ListRestockHistories extends ListRecords
{
    protected static string $resource = RestockHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->label('All'),
            'this_week' => Tab::make('This Week')
                ->label('This Week')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('created_at', '>=', now()->startOfWeek()))
                ->badge(RestockHistory::query()->where('created_at', '>=', now()->startOfWeek())->count())
                ->badgeColor('primary'),
            'this_month' => Tab::make('This Month')
                ->label('This Month')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('created_at', '>=', now()->startOfMonth()))
                ->badge(RestockHistory::query()->where('created_at', '>=', now()->startOfMonth())->count())
                ->badgeColor('primary'),
            'this_year' => Tab::make('This Year')
                ->label('This Year')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('created_at', '>=', now()->startOfYear()))
                ->badge(RestockHistory::query()->where('created_at', '>=', now()->startOfYear())->count())
                ->badgeColor('primary'),
        ];
    }
}
