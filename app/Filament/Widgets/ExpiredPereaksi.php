<?php

namespace App\Filament\Widgets;

use App\Models\RestockHistory;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class ExpiredPereaksi extends BaseWidget
{
    protected static ?string $heading = 'Reagents Expiring in 6 Months';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                RestockHistory::query()
                    ->where('expired_date', '<=', now()->addMonths(6)) // Kurang dari atau sama dengan 6 bulan ke depan
                    ->where('expired_date', '>=', now()) // Masih belum kadaluarsa saat ini
            )
            ->columns([
                Tables\Columns\TextColumn::make('kode_reagent')
                    ->label('Reagent Code')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('nama_reagent')
                    ->label('Reagent Name')
                    ->sortable()
                    ->searchable(),
                    Tables\Columns\TextColumn::make('lot_numbers')
                    ->label('Lot Numbers')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('expired_date')
                    ->label('Expiration Date')
                    ->date()
                    ->sortable(),

            ])
            ->filters([
                // Opsional: Tambahkan filter jika diperlukan
            ])
            ->defaultSort('expired_date', 'asc'); // Urutkan berdasarkan tanggal kadaluarsa terdekat
    }
    protected static ?int $sort = 5;
}