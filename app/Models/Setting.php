<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';
    protected $fillable = ['key', 'value'];

    public function getValueAttribute($value)
    {
        return (bool) $value; // Konversi nilai ke boolean untuk toggle
    }
}
