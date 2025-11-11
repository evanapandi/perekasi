<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PereaksiResource\Pages;
use App\Filament\Resources\PereaksiResource\RelationManagers;
use App\Models\Pereaksi;
use App\Filament\Imports\PereaksiImporter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\ImportAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\ActionGroup;
use OpenSpout\Writer\XLSX\Writer as XLSXWriter;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Entity\Style\CellAlignment;


class PereaksiResource extends Resource
{
    protected static ?string $model = Pereaksi::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Reagent';
    protected static ?string $slug = 'reagent';
    protected static ?string $label = 'Reagent';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('kode_reagent')
                    ->required()
                    ->unique(
                        table: Pereaksi::class,
                        column: 'kode_reagent',
                        ignorable: fn ($record) => $record
                    ) 
                    ->label('Kode Reagent')
                    ->placeholder('Masukkan kode reagent'),
                TextInput::make('nama_reagent')
                    ->required()
                    ->label('Nama Reagent')
                    ->placeholder('Masukkan nama reagent'),
                Select::make('jenis_reagent')
                    ->required()
                    ->label('Jenis Reagent')
                    ->options([
                        'Buffer Solution' => 'Buffer Solution',
                        'Corrosive' => 'Corrosive',
                        'Flammable' => 'Flammable',
                        'Harmful' => 'Harmful',
                        'Irritant' => 'Irritant',
                        'Oxidizing' => 'Oxidizing',
                        'Toxic' => 'Toxic',
                    ])
                    ->placeholder('Pilih jenis reagent'),
                TextInput::make('chemical_formula')
                    ->required()
                    ->label('Chemical Formula')
                    ->placeholder('Masukkan chemical formula'),
                TextInput::make('Stock')
                    ->required()
                    ->numeric()
                    ->label('Jumlah Stock')
                    ->placeholder('Masukkan jumlah stock'),
                Select::make('satuan')
                    ->options(['Gram' => 'Gram', 'Liter' => 'Liter'])
                    ->required()
                    ->placeholder('Pilih satuan'),

                TextInput::make('min_stock')
                    ->label('Minimum Stock')
                    ->numeric()
                    ->required()
                    ->placeholder('Masukkan minimum stock'), 
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode_reagent')
                    ->label('Kode Reagent')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('nama_reagent')
                    ->label('Nama Reagent')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('jenis_reagent')
                    ->label('Jenis Reagent')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('chemical_formula')
                    ->label('Chemical Formula')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('Stock')
                    ->label('Stock')
                    ->sortable()
                    ->searchable()
                    ->suffix(fn (Pereaksi $record) => ' ' . $record->satuan),
                // TextColumn::make('lot_numbers')
                //     ->label('Lot No.')
                //     ->listWithLineBreaks()
                //     ->limitList(1)
                //     ->expandableLimitedList()
                //     ->searchable(),
                // TextColumn::make('expired_date')
                //     ->date()
                //     ->label('Expires')
                //     ->sortable()
                //     ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'In Stock' => 'success',
                        'Under Stock' => 'warning',
                        'Out of Stock' => 'danger',
                    })
            ])
            ->filters([
                SelectFilter::make('jenis_reagent')
                    ->label('Jenis Reagent')
                    ->options(Pereaksi::distinct()->pluck('jenis_reagent', 'jenis_reagent')->toArray())
                    ->multiple()
                    ->query(fn (Builder $query, array $data): Builder => $query->when($data['values'], fn (Builder $q) => $q->whereIn('jenis_reagent', $data['values'])))
                    ->placeholder('Pilih jenis reagent'),
                Filter::make('stock_range')
                    ->form([
                        TextInput::make('stock_min')
                            ->label('Stok Minimum')
                            ->numeric()
                            ->placeholder('Masukkan stok minimum'),
                        TextInput::make('stock_max')
                            ->label('Stok Maksimum')
                            ->numeric()
                            ->placeholder('Masukkan stok maksimum'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['stock_min'], fn (Builder $q) => $q->where('Stock', '>=', $data['stock_min']))
                            ->when($data['stock_max'], fn (Builder $q) => $q->where('Stock', '<=', $data['stock_max']));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['stock_min']) {
                            $indicators[] = 'Stok Min: ' . $data['stock_min'];
                        }
                        if ($data['stock_max']) {
                            $indicators[] = 'Stok Max: ' . $data['stock_max'];
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])->iconButton()
                
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('edit_multiple')
                        ->label('Edit Multiple')
                        ->icon('heroicon-o-pencil-square')
                        ->form([
                            Select::make('jenis_reagent')
                                ->label('Jenis Reagent')
                                ->options([
                                    'Corrosive' => 'Corrosive',
                                    'Flammable' => 'Flammable',
                                    'Harmful' => 'Harmful',
                                    'Irritant' => 'Irritant',
                                    'Oxidizing' => 'Oxidizing',
                                    'Toxic' => 'Toxic',
                                ])
                                ->placeholder('Pilih jenis reagent'),
                        ])
                        ->action(function (array $data, $records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'jenis_reagent' => $data['jenis_reagent'] ?? $record->jenis_reagent,
                                    'Stock' => $data['Stock'] ?? $record->Stock,
                                ]);
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_excel')
                    ->label('Export Excel/CSV')
                    ->icon('heroicon-o-table-cells')
                    ->color('success')
                    ->form([
                        Select::make('jenis_reagent')
                            ->label('Jenis Reagent')
                            ->options(Pereaksi::distinct()->pluck('jenis_reagent', 'jenis_reagent')->toArray())
                            ->multiple()
                            ->placeholder('Pilih jenis reagent'),
                        Select::make('format')
                            ->label('Format File')
                            ->options([
                                'xlsx' => 'Excel (XLSX)',
                                'csv' => 'CSV',
                            ])
                            ->default('xlsx')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $records = Pereaksi::query()
                            ->when($data['jenis_reagent'] ?? null, fn ($q) => $q->whereIn('jenis_reagent', $data['jenis_reagent']))
                            ->orderBy('kode_reagent')
                            ->get();

                        $fileName = 'Stock-Opname-' . now()->format('YmdHis') . '.' . $data['format'];
                        $mimeType = $data['format'] === 'csv'
                            ? 'text/csv'
                            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

                        return response()->streamDownload(function () use ($records, $data) {
                            if ($data['format'] === 'csv') {
                                $handle = fopen('php://output', 'w');
                                fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

                                fputcsv($handle, ['No', 'Kode Reagent', 'Nama Reagent', 'Jenis Reagent', 'Stock', 'Satuan', 'Lot Numbers', 'Minimum Stock', 'Tanggal Kedaluwarsa', 'Status']);

                                foreach ($records as $index => $record) {
                                    fputcsv($handle, [
                                        $index + 1,
                                        $record->kode_reagent,
                                        $record->nama_reagent,
                                        $record->jenis_reagent,
                                        number_format($record->Stock, 2),
                                        $record->satuan,
                                        is_array($record->lot_numbers) ? implode(', ', $record->lot_numbers) : $record->lot_numbers,
                                        $record->min_stock,
                                        optional($record->expired_date)->format('Y-m-d'),
                                        $record->status,
                                    ]);
                                }

                                fclose($handle);
                            } else {
                                $options = new Options();
                                $options->setTempFolder(sys_get_temp_dir());
                                $writer = new XLSXWriter($options);
                                $writer->openToFile('php://output');

                                $headerStyle = (new Style())
                                    ->setFontBold()
                                    ->setFontSize(12)
                                    ->setFontName('Arial')
                                    ->setCellAlignment(CellAlignment::CENTER);

                                $cellStyle = (new Style())
                                    ->setFontSize(11)
                                    ->setFontName('Arial');

                                $writer->addRow(Row::fromValues(['No', 'Kode Reagent', 'Nama Reagent', 'Jenis Reagent', 'Stock', 'Satuan', 'Lot Numbers', 'Minimum Stock', 'Tanggal Kedaluwarsa', 'Status'], $headerStyle));

                                foreach ($records as $index => $record) {
                                    $writer->addRow(Row::fromValues([
                                        $index + 1,
                                        $record->kode_reagent,
                                        $record->nama_reagent,
                                        $record->jenis_reagent,
                                        number_format($record->Stock, 2),
                                        $record->satuan,
                                        is_array($record->lot_numbers) ? implode(', ', $record->lot_numbers) : $record->lot_numbers,
                                        $record->min_stock,
                                        optional($record->expired_date)->format('Y-m-d'),
                                        $record->status,
                                    ], $cellStyle));
                                }

                                $writer->close();
                            }
                        }, $fileName, [
                            'Content-Type' => $mimeType,
                            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                        ]);
                    }),
                ImportAction::make()->importer(PereaksiImporter::class)
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
            'index' => Pages\ListPereaksis::route('/'),
            'create' => Pages\CreatePereaksi::route('/create'),
            'edit' => Pages\EditPereaksi::route('/{record}/edit'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return Pereaksi::query()->orderBy('kode_reagent');
    }

    public static function getRecordRouteKeyName(): string
    {
        return 'kode_reagent'; // Gunakan ID sebagai parameter di URL
    }
}
