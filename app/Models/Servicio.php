<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Servicio extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'categoria',
        'precio_base',
        'duracion_min',
        'descripcion',
    ];

    protected $casts = [
        'precio_base' => 'decimal:2',
        'duracion_min' => 'integer',
    ];

    public function citas(): BelongsToMany
    {
        return $this->belongsToMany(Cita::class, 'cita_servicio')
                    ->withPivot('precio_momento', 'duracion_momento')
                    ->withTimestamps();
    }
}