<?php

namespace App\Filament\Resources\UsageHistoryResource\Pages;

use App\Filament\Resources\UsageHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\UsageHistory;

class ListUsageHistories extends ListRecords
{
    protected static string $resource = UsageHistoryResource::class;

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
                ->badge(UsageHistory::query()->where('created_at', '>=', now()->startOfWeek())->count())
                ->badgeColor('primary'),
            'this_month' => Tab::make('This Month')
                ->label('This Month')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('created_at', '>=', now()->startOfMonth()))
                ->badge(UsageHistory::query()->where('created_at', '>=', now()->startOfMonth())->count())
                ->badgeColor('primary'),
            'this_year' => Tab::make('This Year')
                ->label('This Year')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('created_at', '>=', now()->startOfYear()))
                ->badge(UsageHistory::query()->where('created_at', '>=', now()->startOfYear())->count())
                ->badgeColor('primary'),
        ];
    }
}
