<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pereaksi;

class RestockHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_reagent',
        'nama_reagent',
        'jenis_reagent',
        'lot_numbers',
        'receipt_date',
        'location',
        'expired_date',
        'jumlah_restock',
        'certificate_of_analysis',
        'satuan',
        'nama_analys'
    ];
    
    public $timestamps = true;

    protected static function boot()
    {
        parent::boot();

        // Menambahkan stok otomatis saat restock history dibuat
        static::created(function ($restockHistory) {
            $pereaksi = Pereaksi::where('kode_reagent', $restockHistory->kode_reagent)->first();
            if ($pereaksi) {
                $pereaksi->Stock += $restockHistory->jumlah_restock;
                $pereaksi->save(); // Ini akan trigger observer untuk update status
            }
        });

        // Handle update jika jumlah restock diubah
        static::updated(function ($restockHistory) {
            $pereaksi = Pereaksi::where('kode_reagent', $restockHistory->kode_reagent)->first();
            
            if ($pereaksi && $restockHistory->isDirty('jumlah_restock')) {
                // Jika jumlah_restock berubah, update stok
                $oldAmount = $restockHistory->getOriginal('jumlah_restock');
                $newAmount = $restockHistory->jumlah_restock;
                $difference = $newAmount - $oldAmount;
                
                $pereaksi->Stock += $difference;
                $pereaksi->save();
            }
        });

        // Handle delete - kurangi stok jika history dihapus
        static::deleted(function ($restockHistory) {
            $pereaksi = Pereaksi::where('kode_reagent', $restockHistory->kode_reagent)->first();
            
            if ($pereaksi) {
                $pereaksi->Stock -= $restockHistory->jumlah_restock;
                // Pastikan stok tidak negatif
                if ($pereaksi->Stock < 0) {
                    $pereaksi->Stock = 0;
                }
                $pereaksi->save();
            }
        });
    }

    public function pereaksi()
    {
        return $this->belongsTo(Pereaksi::class, 'kode_reagent', 'kode_reagent');
    }
}
