<?php

namespace App\Services;

use App\Models\Cita;
use Illuminate\Support\Facades\Auth;

class EspecialistaPanelService
{
    public function getPanelData(?string $fechaInicio = null, ?string $fechaFin = null): array
    {
        $usuario = Auth::user();
        $fechaInicio = $fechaInicio ?: now()->startOfMonth()->format('Y-m-d');
        $fechaFin = $fechaFin ?: now()->endOfMonth()->format('Y-m-d');

        $usuarioId = $usuario ? $usuario->id : null;

        $citas = Cita::with(['paciente', 'servicios'])
            ->where('user_id', $usuarioId)
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->orderBy('fecha', 'desc')
            ->get();

        $comisionTotal = (float) $citas->sum('monto_total');

        $nombreEspecialista = $usuario ? $usuario->name : 'Especialista';
        $especialidad = ($usuario && $usuario->role === 'especialista') ? 'Especialista' : 'Administrador';

        return [
            'especialista' => [
                'nombre' => $nombreEspecialista,
                'especialidad' => $especialidad,
            ],
            'comision_total' => $comisionTotal,
            'filters' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
            ],
            'atenciones' => $citas->map(function ($cita) {
                $paciente = $cita->paciente ? $cita->paciente->nombre : null;
                $servicio = $cita->servicios->pluck('nombre')->join(', ');

                return [
                    'fecha' => $cita->fecha ? $cita->fecha->format('d/m/Y') : 'N/A',
                    'paciente' => $paciente ?? 'Paciente no registrado',
                    'servicio' => $servicio ?: 'Atención General',
                    'monto' => (float) $cita->monto_total,
                ];
            })->values()->all(),
        ];
    }
}
