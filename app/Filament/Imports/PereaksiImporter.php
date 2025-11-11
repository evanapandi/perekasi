<?php

namespace App\Filament\Imports;

use App\Models\Pereaksi;
use App\Models\RestockHistory;
use App\Models\UsageHistory;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Auth;

class PereaksiImporter extends Importer
{
    protected static ?string $model = Pereaksi::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('kode_reagent')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('nama_reagent')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('jenis_reagent')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('Stock')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('satuan')
                ->rules(['max:6']),
            ImportColumn::make('lot_numbers')
                ->castStateUsing(function ($state) {
                    // Jika string dengan koma, ubah jadi array
                    if (is_string($state) && strpos($state, ',') !== false) {
                        return array_map('trim', explode(',', $state));
                    }
                    // Jika sudah array (misalnya dari JSON), kembalikan apa adanya
                    return is_array($state) ? $state : [$state];
                })
                ->rules(['nullable']),
            ImportColumn::make('min_stock')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('expired_date')
                ->rules(['date']),
        ];
    }

    public function resolveRecord(): ?Pereaksi
    {
        $existingRecord = Pereaksi::where('kode_reagent', $this->data['kode_reagent'])->first();

        if ($existingRecord) {
            // Ambil stok lama
            $oldStock = $existingRecord->Stock;
            $newStock = (int) $this->data['Stock'];

            // Hitung perbedaan stok
            $stockDifference = $newStock - $oldStock;

            // Jika stok bertambah, catat ke RestockHistory
            if ($stockDifference > 0) {
                RestockHistory::create([
                    'kode_reagent' => $this->data['kode_reagent'],
                    'nama_reagent' => $this->data['nama_reagent'],
                    'jenis_reagent' => $this->data['jenis_reagent'],
                    'jumlah_restock' => $stockDifference,
                    'satuan' => $this->data['satuan'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            // Jika stok berkurang, catat ke UsageHistory
            elseif ($stockDifference < 0) {
                UsageHistory::create([
                    'nama_analis' => Auth::user()->name ?? 'Imported', // Default ke user yang login atau "Imported"
                    'kode_reagent' => $this->data['kode_reagent'],
                    'nama_reagent' => $this->data['nama_reagent'],
                    'jenis_reagent' => $this->data['jenis_reagent'],
                    'jumlah_penggunaan' => abs($stockDifference), // Gunakan nilai absolut untuk jumlah penggunaan
                    'satuan' => $this->data['satuan'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        return Pereaksi::firstOrNew([
            // Update existing records, matching them by `$this->data['column_name']`
            'kode_reagent' => $this->data['kode_reagent'],
        ]);

        // return new Pereaksi();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your pereaksi import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}