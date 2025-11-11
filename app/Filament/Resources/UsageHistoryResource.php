<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UsageHistoryResource\Pages;
use App\Filament\Resources\UsageHistoryResource\RelationManagers;
use App\Models\UsageHistory;
use App\Models\User;
use App\Models\Pereaksi;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use OpenSpout\Writer\XLSX\Writer as XLSXWriter;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Entity\Style\CellAlignment;

class UsageHistoryResource extends Resource
{
    protected static ?string $model = UsageHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'History';
    protected static ?string $label = 'Usage History';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('nama_analis')
                    ->label('Nama Analis')
                    ->options(User::all()->pluck('name', 'name')->toArray()) // Ambil nama dari tabel User
                    ->reactive()
                    ->searchable()
                    ->required()
                    ->default(auth()->user()->name), // Default ke nama user yang login
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
                    ->readOnly()
                    ->required(),
                TextInput::make('jenis_reagent')
                    ->label('Jenis Reagent')
                    ->readOnly()
                    ->required(),
                TextInput::make('jumlah_penggunaan')
                    ->label('Jumlah Penggunaan')
                    ->numeric()
                    ->required()
                    ->minValue(1),
                TextInput::make('satuan')
                    ->label('Satuan')
                    ->readOnly(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_analis')
                    ->label('Nama Analis')
                    ->sortable()
                    ->searchable(),
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
                TextColumn::make('jumlah_penggunaan')
                    ->label('Jumlah Penggunaan')
                    ->sortable()
                    ->searchable()
                    ->suffix(fn (UsageHistory $record) => ' ' . $record->satuan),
                TextColumn::make('created_at')
                    ->label('Tanggal Penggunaan')
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
                        $records = UsageHistory::query()
                            ->when($data['start_date'], fn ($q) => $q->whereDate('created_at', '>=', $data['start_date']))
                            ->when($data['end_date'], fn ($q) => $q->whereDate('created_at', '<=', $data['end_date']))
                            ->orderBy('created_at')
                            ->get();

                        $fileName = 'Usage-History-' . now()->format('YmdHis') . '.' . $data['format'];
                        $mimeType = $data['format'] === 'csv'
                            ? 'text/csv'
                            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

                        return response()->streamDownload(function () use ($records, $data) {
                            if ($data['format'] === 'csv') {
                                $handle = fopen('php://output', 'w');
                                fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

                                fputcsv($handle, ['No', 'Nama Analis', 'Kode Reagent', 'Nama Reagent', 'Jenis Reagent', 'Jumlah Penggunaan', 'Satuan', 'Tanggal Penggunaan']);

                                foreach ($records as $index => $record) {
                                    fputcsv($handle, [
                                        $index + 1,
                                        $record->nama_analis,
                                        $record->kode_reagent,
                                        $record->nama_reagent,
                                        $record->jenis_reagent,
                                        number_format($record->jumlah_penggunaan, 2),
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

                                $writer->addRow(Row::fromValues(['No', 'Nama Analis', 'Kode Reagent', 'Nama Reagent', 'Jenis Reagent', 'Jumlah Penggunaan', 'Satuan', 'Tanggal Penggunaan'], $headerStyle));

                                foreach ($records as $index => $record) {
                                    $writer->addRow(Row::fromValues([
                                        $index + 1,
                                        $record->nama_analis,
                                        $record->kode_reagent,
                                        $record->nama_reagent,
                                        $record->jenis_reagent,
                                        number_format($record->jumlah_penggunaan, 2),
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
            'index' => Pages\ListUsageHistories::route('/'),
            'create' => Pages\CreateUsageHistory::route('/create'),
            'edit' => Pages\EditUsageHistory::route('/{record}/edit'),
        ];
    }
}
