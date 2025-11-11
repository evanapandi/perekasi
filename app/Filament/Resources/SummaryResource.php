<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Summary;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\UsageHistory;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Exports\SummaryExporter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\ExportBulkAction;
use App\Filament\Resources\SummaryResource\Pages;
use Filament\Forms\Components\Select as FormSelect;
use App\Filament\Resources\SummaryResource\RelationManagers;
use OpenSpout\Writer\XLSX\Writer as XLSXWriter;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Entity\Style\CellAlignment;

class SummaryResource extends Resource
{
    protected static ?string $model = Summary::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Report';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_reagent')
                    ->label('Nama Reagent')
                    ->required()
                    ->disabled(),
                Forms\Components\TextInput::make('total_penggunaan')
                    ->label('Total Penggunaan')
                    ->required()
                    ->numeric()
                    ->disabled()
                    ->suffix(fn (Summary $record) => ' ' . $record->satuan),
                Forms\Components\TextInput::make('satuan')
                    ->label('Satuan')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_reagent')
                    ->label('Nama Reagent')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_penggunaan')
                    ->label('Total Penggunaan')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 2))
                    ->summarize([
                        // Tables\Columns\Summarizers\Sum::make()->label('Total Keseluruhan'),
                        Tables\Columns\Summarizers\Count::make()->label('Jumlah Entri'),
                    ])
                    ->suffix(fn (Summary $record) => ' ' . $record->satuan),
            ])
            ->filters([
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('start_date')
                            ->label('From'),
                        DatePicker::make('end_date')
                            ->label('To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['start_date'] || $data['end_date']) {
                            Summary::updateSummaryWithFilters($data['start_date'], $data['end_date']);
                        } else {
                            // Jika filter kosong, update summary tanpa filter
                            Summary::updateSummary();
                        }
                        return $query;
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['start_date']) {
                            $indicators[] = 'Dari: ' . $data['start_date'];
                        }
                        if ($data['end_date']) {
                            $indicators[] = 'Sampai: ' . $data['end_date'];
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()->exporter(SummaryExporter::class),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_excel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-table-cells')
                    ->color('success')
                    ->form([
                        DatePicker::make('start_date')
                            ->label('Dari Tanggal')
                            ->default(now()->startOfMonth())
                            ->required(),
                        DatePicker::make('end_date')
                            ->label('Sampai Tanggal')
                            ->default(now()->endOfMonth())
                            ->required(),
                        FormSelect::make('jenis_reagent')
                            ->label('Jenis Reagent')
                            ->options(\App\Models\Pereaksi::pluck('jenis_reagent', 'jenis_reagent')->unique()->toArray())
                            ->multiple()
                            ->placeholder('Pilih jenis reagent'),
                        FormSelect::make('nama_reagent')
                            ->label('Nama Reagent')
                            ->options(Summary::pluck('nama_reagent', 'nama_reagent')->toArray())
                            ->multiple()
                            ->placeholder('Pilih nama reagent'),
                        FormSelect::make('format')
                            ->label('Format File')
                            ->options([
                                'xlsx' => 'Excel (XLSX)',
                                'csv' => 'CSV',
                            ])
                            ->default('xlsx')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        // Query data berdasarkan filter
                        $query = UsageHistory::query()
                            ->selectRaw('nama_reagent, satuan, SUM(jumlah_penggunaan) as total_penggunaan')
                            ->groupBy('nama_reagent', 'satuan')
                            ->when($data['start_date'], fn ($q) => $q->where('created_at', '>=', $data['start_date']))
                            ->when($data['end_date'], fn ($q) => $q->where('created_at', '<=', $data['end_date'] . ' 23:59:59'))
                            ->when($data['jenis_reagent'] ?? null, fn ($q) => $q->whereHas('pereaksi', fn ($q) => $q->whereIn('jenis_reagent', $data['jenis_reagent'])))
                            ->when($data['nama_reagent'] ?? null, fn ($q) => $q->whereIn('nama_reagent', $data['nama_reagent']));

                        $summaries = $query->get();

                        $fileName = 'Laporan-Penggunaan-Reagent-' . now()->format('YmdHis') . '.' . $data['format'];
                        $mimeType = $data['format'] === 'csv' ? 'text/csv' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

                        return response()->streamDownload(function () use ($summaries, $data) {
                            if ($data['format'] === 'csv') {
                                // Export CSV
                                $file = fopen('php://output', 'w');
                                
                                // BOM untuk UTF-8
                                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                                
                                // Header
                                fputcsv($file, ['ID', 'Nama Reagent', 'Total Penggunaan', 'Satuan', 'Tanggal Dibuat']);
                                
                                // Data
                                $id = 1;
                                foreach ($summaries as $summary) {
                                    fputcsv($file, [
                                        $id++,
                                        $summary->nama_reagent,
                                        number_format($summary->total_penggunaan, 2),
                                        $summary->satuan,
                                        now()->format('Y-m-d H:i:s'),
                                    ]);
                                }
                                
                                fclose($file);
                            } else {
                                // Export XLSX
                                $options = new Options();
                                $options->setTempFolder(sys_get_temp_dir());
                                $writer = new XLSXWriter($options);
                                $writer->openToFile('php://output');

                                // Style untuk header
                                $headerStyle = (new Style())
                                    ->setFontBold()
                                    ->setFontSize(12)
                                    ->setFontName('Arial')
                                    ->setCellAlignment(CellAlignment::CENTER)
                                    ->setBackgroundColor('4CAF50')
                                    ->setFontColor('FFFFFF');

                                // Style untuk cell
                                $cellStyle = (new Style())
                                    ->setFontSize(11)
                                    ->setFontName('Arial');

                                // Header
                                $writer->addRow(Row::fromValues(['ID', 'Nama Reagent', 'Total Penggunaan', 'Satuan', 'Tanggal Dibuat'], $headerStyle));

                                // Data
                                $id = 1;
                                foreach ($summaries as $summary) {
                                    $writer->addRow(Row::fromValues([
                                        $id++,
                                        $summary->nama_reagent,
                                        number_format($summary->total_penggunaan, 2),
                                        $summary->satuan,
                                        now()->format('Y-m-d H:i:s'),
                                    ], $cellStyle));
                                }

                                $writer->close();
                            }
                        }, $fileName, [
                            'Content-Type' => $mimeType,
                            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                        ]);
                    }),
                Tables\Actions\Action::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-text')
                    ->form([
                        DatePicker::make('start_date')
                            ->label('Dari Tanggal')
                            ->default(now()->startOfMonth())
                            ->required(),
                        DatePicker::make('end_date')
                            ->label('Sampai Tanggal')
                            ->default(now()->endOfMonth())
                            ->required(),
                        FormSelect::make('jenis_reagent')
                            ->label('Jenis Reagent')
                            ->options(\App\Models\Pereaksi::pluck('jenis_reagent', 'jenis_reagent')->unique()->toArray())
                            ->multiple()
                            ->placeholder('Pilih jenis reagent'),
                        FormSelect::make('nama_reagent')
                            ->label('Nama Reagent')
                            ->options(Summary::pluck('nama_reagent', 'nama_reagent')->toArray())
                            ->multiple()
                            ->placeholder('Pilih nama reagent'),
                    ])
                    ->action(function (array $data) {
                        $query = UsageHistory::query()
                            ->selectRaw('nama_reagent, satuan, SUM(jumlah_penggunaan) as total_penggunaan')
                            ->groupBy('nama_reagent', 'satuan')
                            ->when($data['start_date'], fn ($q) => $q->where('created_at', '>=', $data['start_date']))
                            ->when($data['end_date'], fn ($q) => $q->where('created_at', '<=', $data['end_date'] . ' 23:59:59'))
                            ->when($data['jenis_reagent'] ?? null, fn ($q) => $q->whereHas('pereaksi', fn ($q) => $q->whereIn('jenis_reagent', $data['jenis_reagent'])))
                            ->when($data['nama_reagent'] ?? null, fn ($q) => $q->whereIn('nama_reagent', $data['nama_reagent']));

                        $summaries = $query->get();

                        $pdf = app('dompdf.wrapper')->loadView('pdf.summary-report', [
                            'summaries' => $summaries,
                            'start_date' => $data['start_date'],
                            'end_date' => $data['end_date'],
                        ]);
                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            'Laporan-Penggunaan-Reagent-' . now()->format('YmdHis') . '.pdf'
                        );
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSummaries::route('/'),
            'create' => Pages\CreateSummary::route('/create'),
            'edit' => Pages\EditSummary::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (request()->has('tableFilters')) {
            $filters = request()->get('tableFilters');
            if (!empty($filters['date_range']['start_date']) || !empty($filters['date_range']['end_date'])) {
                Summary::updateSummaryWithFilters(
                    $filters['date_range']['start_date'] ?? null,
                    $filters['date_range']['end_date'] ?? null
                );
            } else {
                // Jika filter kosong, update summary tanpa filter
                Summary::updateSummary();
            }
        } else {
            // Jika tidak ada filter sama sekali, pastikan data summary sudah ada
            // Cek apakah ada data di tabel summary
            if (Summary::count() === 0) {
                Summary::updateSummary();
            }
        }

        return $query;
    }
}