<?php

namespace App\Filament\Exports;

use App\Models\Summary;
use Illuminate\Support\Facades\Log;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\CellAlignment;

class SummaryExporter extends Exporter
{
    protected static ?string $model = Summary::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('nama_reagent')
                ->label('Nama Reagent'),
            ExportColumn::make('total_penggunaan')
                ->label('Total Penggunaan')
                ->formatStateUsing(fn ($state, Summary $record) => number_format($state, 2) . ' ' . $record->satuan),
            ExportColumn::make('created_at')
                ->label('Tanggal Dibuat'),
            ExportColumn::make('updated_at')
                ->label('Tanggal Diperbarui'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your summary export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }

    public function getXlsxHeaderCellStyle(): ?Style
    {
        return (new Style())
            ->setFontBold()
            ->setFontSize(12)
            ->setFontName('Arial')
            ->setCellAlignment(CellAlignment::CENTER)
            ->setBackgroundColor('4CAF50')
            ->setFontColor('FFFFFF')
            ->setBorder(
                new Border(
                    new Border\BorderPart(Border::BOTTOM, '000000', Border::WIDTH_THIN)
                )
            );
    }

    public function getXlsxCellStyle(): ?Style
    {
        return (new Style())
            ->setFontSize(11)
            ->setFontName('Arial')
            ->setBorder(
                new Border(
                    new Border\BorderPart(Border::TOP, 'D3D3D3', Border::WIDTH_THIN),
                    new Border\BorderPart(Border::RIGHT, 'D3D3D3', Border::WIDTH_THIN),
                    new Border\BorderPart(Border::BOTTOM, 'D3D3D3', Border::WIDTH_THIN),
                    new Border\BorderPart(Border::LEFT, 'D3D3D3', Border::WIDTH_THIN)
                )
            );
    }

    public function export(Export $export): void
    {
        Log::info('Starting Excel Export', ['export_id' => $export->id]);
        parent::export($export);
        Log::info('Finished Excel Export', ['export_id' => $export->id]);
    }
}
