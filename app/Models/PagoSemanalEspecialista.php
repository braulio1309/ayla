<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoSemanalEspecialista extends Model
{
    use HasFactory;

    protected $table = 'pagos_semanales_especialistas';

    protected $fillable = [
        'especialista_id',
        'registrado_por_id',
        'semana_inicio',
        'semana_fin',
        'monto_pagado',
        'monto_pagado_bs',
        'pagado_at',
    ];

    protected $casts = [
        'semana_inicio' => 'date',
        'semana_fin' => 'date',
        'monto_pagado' => 'decimal:2',
        'monto_pagado_bs' => 'decimal:2',
        'pagado_at' => 'datetime',
    ];

    public function especialista(): BelongsTo
    {
        return $this->belongsTo(User::class, 'especialista_id');
    }
}