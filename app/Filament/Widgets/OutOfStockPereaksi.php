<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Pereaksi;

class OutOfStockPereaksi extends BaseWidget
{
    protected int | string | array $columnSpan = 1;
    public function getTableHeading(): string
    {
        return "Out of Stock Reagent";
    }
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Pereaksi::query()->where('Stock', 0)
            )
            ->columns([
                TextColumn::make('kode_reagent')
                    ->label('Kode Reagent')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('nama_reagent')
                    ->label('Nama Reagent')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('Status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'In Stock' => 'success',
                        'Under Stock' => 'warning',
                        'Out of Stock' => 'danger',
                    })
            ]);
    }
    protected static ?int $sort = 7;
}
