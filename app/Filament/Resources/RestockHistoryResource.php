<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RestockHistoryResource\Pages;
use App\Filament\Resources\RestockHistoryResource\RelationManagers;
use App\Models\RestockHistory;
use App\Models\Pereaksi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use OpenSpout\Writer\XLSX\Writer as XLSXWriter;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Entity\Style\CellAlignment;

class RestockHistoryResource extends Resource
{
    protected static ?string $model = RestockHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'History';
    protected static ?string $label = 'Restock History';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('nama_reagent')
                    ->label('Nama Reagent')
                    ->options(Pereaksi::all()->pluck('nama_reagent', 'nama_reagent')->toArray())
                    ->reactive()
                    ->searchable()
                    ->required()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $pereaksi = Pereaksi::where('nama_reagent', $state)->first();
                        if ($pereaksi) {
                            $set('kode_reagent', $pereaksi->kode_reagent);
                            $set('jenis_reagent', $pereaksi->jenis_reagent);
                            $set('satuan', $pereaksi->satuan);
                        }
                    }),
                TextInput::make('kode_reagent')
                    ->label('Kode Reagent')
                    ->readOnly() // Ganti disabled() dengan readOnly()
                    ->required(),
                TextInput::make('jenis_reagent')
                    ->label('Jenis Reagent')
                    ->readOnly()
                    ->required(),
                TextInput::make('satuan')
                    ->label('Satuan')
                    ->readOnly(),
                TextInput::make('jumlah_restock')
                    ->label('Jumlah Restock')
                    ->numeric()
                    ->required()
                    ->minValue(1),
                TextInput::make('lot_numbers')
                    ->label('Lot Numbers')
                    ->placeholder('Masukkan lot numbers'),
                DatePicker::make('receipt_date')
                    ->label('Receipt Date')
                    ->required(),
                DatePicker::make('expired_date')
                    ->label('Expiration Date')
                    ->required(),
                FileUpload::make('image')
                    ->label('Foto Barang')
                    ->image() // hanya izinkan file gambar
                    ->directory('restocks') // simpan di storage/app/public/restocks
                    ->preserveFilenames() // biar nama file tetap
                    ->imagePreviewHeight('200') // tampilkan preview
                    ->downloadable() // bisa di-download
                    ->openable() // bisa dibuka langsung
                    ->maxSize(2048),// max 2MB
                TextInput::make('location')
                    ->label('Location')
                    ->placeholder('Location'), 

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
                TextColumn::make('jumlah_restock')
                    ->label('Jumlah Restock')
                    ->sortable()
                    ->searchable()
                    ->suffix(fn (RestockHistory $record) => ' ' . $record->satuan),
                TextColumn::make('lot_numbers')
                    ->label('Lot Numbers')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Tanggal Restock')
                    ->date('d-m-Y')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('expired_date')
                    ->label('Expiration Date')
                    ->date('d-m-Y')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('location')
                    ->label('Location')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('From'),
                        DatePicker::make('created_until')
                            ->label('To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_excel')
                    ->label('Export Excel/CSV')
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
                        $records = RestockHistory::query()
                            ->when($data['start_date'], fn ($q) => $q->whereDate('created_at', '>=', $data['start_date']))
                            ->when($data['end_date'], fn ($q) => $q->whereDate('created_at', '<=', $data['end_date']))
                            ->orderBy('created_at')
                            ->get();

                        $fileName = 'Restock-History-' . now()->format('YmdHis') . '.' . $data['format'];
                        $mimeType = $data['format'] === 'csv'
                            ? 'text/csv'
                            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

                        return response()->streamDownload(function () use ($records, $data) {
                            if ($data['format'] === 'csv') {
                                $handle = fopen('php://output', 'w');
                                fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

                                fputcsv($handle, ['No', 'Kode Reagent', 'Nama Reagent', 'Jenis Reagent', 'Jumlah Restock', 'Satuan', 'Tanggal Restock']);

                                foreach ($records as $index => $record) {
                                    fputcsv($handle, [
                                        $index + 1,
                                        $record->kode_reagent,
                                        $record->nama_reagent,
                                        $record->jenis_reagent,
                                        number_format($record->jumlah_restock, 2),
                                        $record->satuan,
                                        optional($record->created_at)->format('Y-m-d H:i:s'),
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

                                $writer->addRow(Row::fromValues(['No', 'Kode Reagent', 'Nama Reagent', 'Jenis Reagent', 'Jumlah Restock', 'Satuan', 'Tanggal Restock'], $headerStyle));

                                foreach ($records as $index => $record) {
                                    $writer->addRow(Row::fromValues([
                                        $index + 1,
                                        $record->kode_reagent,
                                        $record->nama_reagent,
                                        $record->jenis_reagent,
                                        number_format($record->jumlah_restock, 2),
                                        $record->satuan,
                                        optional($record->created_at)->format('Y-m-d H:i:s'),
                                    ], $cellStyle));
                                }

                                $writer->close();
                            }
                        }, $fileName, [
                            'Content-Type' => $mimeType,
                            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                        ]);
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
            'index' => Pages\ListRestockHistories::route('/'),
            'create' => Pages\CreateRestockHistory::route('/create'),
            'edit' => Pages\EditRestockHistory::route('/{record}/edit'),
        ];
    }

}
