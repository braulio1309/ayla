<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\User;

class Servicio extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'categoria',
        'precio_base',
        'comision_adicional',
        'duracion_min',
        'es_recurrente',
        'descripcion',
    ];

    protected $casts = [
        'precio_base' => 'decimal:2',
        'comision_adicional' => 'decimal:2',
        'duracion_min' => 'integer',
        'es_recurrente' => 'boolean',
    ];

    public function citas(): BelongsToMany
    {
        return $this->belongsToMany(Cita::class, 'cita_servicio')
                    ->withPivot('precio_momento', 'monto_bs_momento', 'duracion_momento', 'especialista_id', 'comision_momento', 'comision_tipo', 'comision_monto', 'requiere_lavado', 'lavado_especialista_id', 'lavado_monto')
                    ->withTimestamps();
    }

    public function especialistas(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'servicio_user')
            ->withPivot('precio_especialista')
            ->withTimestamps();
    }

    public function getPrecioParaEspecialista(int $userId): float
    {
        $especialista = $this->especialistas()->where('users.id', $userId)->first();

        if ($especialista && $especialista->pivot) {
            return (float) ($especialista->pivot->precio_especialista ?? $this->precio_base ?? 0) + (float) ($this->comision_adicional ?? 0);
        }

        return (float) ($this->precio_base ?? 0) + (float) ($this->comision_adicional ?? 0);
    }

    public function getPrecioFinalParaEspecialista(int $userId, ?float $precioManual = null): float
    {
        return $precioManual !== null ? (float) $precioManual : $this->getPrecioParaEspecialista($userId);
    }
}