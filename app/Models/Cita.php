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
        'fecha',
        'hora_inicio',
        'hora_fin',
        'holgura_min',
        'monto_total',
        'estado',
        'cabina',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto_total' => 'decimal:2',
        'holgura_min' => 'integer',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function especialista(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function servicios(): BelongsToMany
    {
        return $this->belongsToMany(Servicio::class, 'cita_servicio')
                    ->withPivot('precio_momento', 'duracion_momento')
                    ->withTimestamps();
    }
}