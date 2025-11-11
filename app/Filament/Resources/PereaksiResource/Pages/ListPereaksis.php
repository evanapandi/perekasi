<?php

namespace App\Filament\Resources\PereaksiResource\Pages;

use App\Filament\Resources\PereaksiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Pereaksi;

class ListPereaksis extends ListRecords
{
    protected static string $resource = PereaksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array 
    {
        return [
            // 'All' => Tab::make(),
            // 'Corrosive' => Tab::make()
            //     ->modifyQueryUsing(fn (Builder $query) => $query->where('jenis_reagent', 'Corrosive Chemicals')),
            // 'Flammable' => Tab::make()
            //     ->modifyQueryUsing(fn (Builder $query) => $query->where('jenis_reagent', 'Flammable Chemicals')),
            // 'Harmful' => Tab::make()
            //     ->modifyQueryUsing(fn (Builder $query) => $query->where('jenis_reagent', 'Harmful Chemicals')),
            // 'Irritant' => Tab::make()
            //     ->modifyQueryUsing(fn (Builder $query) => $query->where('jenis_reagent', 'Irritant Chemicals')),
            // 'Oxidizing' => Tab::make()
            //     ->modifyQueryUsing(fn (Builder $query) => $query->where('jenis_reagent', 'Oxidizing Chemicals')),
            // 'Toxic' => Tab::make()
            //     ->modifyQueryUsing(fn (Builder $query) => $query->where('jenis_reagent', 'Toxic Chemicals')),
            'All' => Tab::make(),
            'In Stock' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('Stock', '>', 500))
                ->badge(Pereaksi::query()->where('Stock', '>', 500)->count())
                ->badgeColor('success'),
            'Under Stock' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('Stock', '>', 0)->where('Stock', '<=', 500))
                ->badge(Pereaksi::query()->where('Stock', '>', 0)->where('Stock', '<=', 500)->count())
                ->badgeColor('warning'),
            'Out of Stock' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('Stock', '=', 0))
                ->badge(Pereaksi::query()->where('Stock', '=', 0)->count())
                ->badgeColor('danger'),
        ];
    }

}
