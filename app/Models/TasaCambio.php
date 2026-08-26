<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TasaCambio extends Model
{
    protected $table = 'tasas_cambio';

    protected $fillable = [
        'dolar_bcv',
        'euro_bcv',
        'actualizada_en',
    ];

    protected $casts = [
        'dolar_bcv' => 'decimal:4',
        'euro_bcv' => 'decimal:4',
        'actualizada_en' => 'datetime',
    ];
}
