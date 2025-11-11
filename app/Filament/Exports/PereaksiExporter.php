<?php

namespace App\Filament\Exports;

use App\Models\Pereaksi;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PereaksiExporter extends Exporter
{
    protected static ?string $model = Pereaksi::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('kode_reagent'),
            ExportColumn::make('nama_reagent'),
            ExportColumn::make('jenis_reagent'),
            ExportColumn::make('Stock'),
            ExportColumn::make('satuan'),
            ExportColumn::make('lot_numbers')
                ->label('Lot Numbers')
                ->getStateUsing(fn (Pereaksi $record) => implode(', ', $record->lot_numbers ?? [])),
            ExportColumn::make('min_stock')->label('Minimum Stock'),
            ExportColumn::make('expired_date')->label('Expiration Date'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your pereaksi export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
