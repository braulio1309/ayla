<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Cita extends Model
{
    use HasFactory;

    protected $fillable = [
        'paciente_id',
        'user_id',
        'asistente_id',
        'comision_asistente_porcentaje',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'holgura_min',
        'monto_total',
        'tasa_dolar_bcv',
        'tasa_euro_bcv',
        'monto_total_bs',
        'estado',
        'cabina',
        'observaciones',
        'whatsapp_creacion_enviado_at',
        'whatsapp_recordatorio_enviado_at',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto_total' => 'decimal:2',
        'tasa_dolar_bcv' => 'decimal:4',
        'tasa_euro_bcv' => 'decimal:4',
        'monto_total_bs' => 'decimal:2',
        'comision_asistente_porcentaje' => 'decimal:2',
        'holgura_min' => 'integer',
        'whatsapp_creacion_enviado_at' => 'datetime',
        'whatsapp_recordatorio_enviado_at' => 'datetime',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function especialista(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function asistente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asistente_id');
    }

    public function servicios(): BelongsToMany
    {
        return $this->belongsToMany(Servicio::class, 'cita_servicio')
                    ->withPivot('precio_momento', 'monto_bs_momento', 'duracion_momento')
                    ->withTimestamps();
    }
}