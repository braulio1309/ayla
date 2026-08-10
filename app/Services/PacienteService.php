<?php

namespace App\Services;

use App\Models\Paciente;

class PacienteService
{
    public function getPacientesData(?string $search = null): array
    {
        $query = Paciente::with(['citas.servicios', 'citas.especialista']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('cedula', 'like', "%{$search}%");
            });
        }

        $pacientes = $query->get()->map(function ($p) {
            return [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'cedula' => $p->cedula,
                'telefono' => $p->telefono,
                'email' => $p->email,
                'notas' => $p->notas,
                'citas_count' => $p->citas ? $p->citas->count() : 0,
                'historial' => $p->citas ? $p->citas->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'fecha' => $c->fecha ? $c->fecha->format('d/m/Y') : 'N/A',
                        'servicios' => $c->servicios ? $c->servicios->pluck('nombre')->join(', ') : 'Atención General',
                        'especialista' => $c->especialista ? $c->especialista->name : 'Especialista Asignado',
                        'monto' => (float) $c->monto_total,
                        'estado' => $c->estado,
                        'observaciones' => $c->observaciones,
                    ];
                }) : [],
            ];
        })->values()->all();

        return [
            'filters' => ['search' => $search],
            'pacientes' => $pacientes,
        ];
    }
}
