<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AylaAdicional extends Model
{
    use HasFactory;

    protected $table = 'ayla_adicionales';

    protected $fillable = [
        'user_id',
        'fecha',
        'descripcion',
        'monto',
        'monto_bs',
        'tasa_euro_bcv',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
        'monto_bs' => 'decimal:2',
        'tasa_euro_bcv' => 'decimal:4',
    ];

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}