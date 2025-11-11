<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pereaksi extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'pereaksi';
    protected $primaryKey = 'kode_reagent';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'kode_reagent',
        'nama_reagent',
        'jenis_reagent',
        'chemical_formula',
        'Stock',
        'satuan',
        'min_stock',
    ];

    // Explicitly cast KODE to string if needed
    protected $casts = [
        'kode_reagent' => 'string',
        'Stock' => 'float',
    ];
    
    public function usageHistories()
    {
        return $this->hasMany(UsageHistory::class, 'kode_reagent', 'kode_reagent');
    }

    public function restockHistories()
    {
        return $this->hasMany(RestockHistory::class, 'kode_reagent', 'kode_reagent');
    }

    public function getStatusAttribute(): string
    {
        $stock = $this->Stock;
        $minStock = $this->min_stock;

        if ($stock == 0) {
            return 'Out of Stock';
        }
        
        if ($stock < $minStock) {
            return 'Under Stock';
        }
        
        return 'In Stock';
    }
    
    protected $guarded = [];
}
