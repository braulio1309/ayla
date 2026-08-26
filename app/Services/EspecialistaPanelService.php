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

        $citas = Cita::with(['paciente', 'servicios', 'especialista'])
            ->where(function ($query) use ($usuarioId) {
                $query->where('user_id', $usuarioId)
                    ->orWhere('asistente_id', $usuarioId);
            })
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->orderBy('fecha', 'desc')
            ->get();

        $citasPrincipales = $citas->where('user_id', $usuarioId);
        $citasAsistidas = $citas->where('asistente_id', $usuarioId)->where('user_id', '!=', $usuarioId);
        $totalGenerado = (float) $citasPrincipales->sum('monto_total');
        $totalGeneradoBs = (float) $citasPrincipales->sum('monto_total_bs');
        $porcentajeComision = $usuario && $usuario->role === 'especialista' ? (float) ($usuario->comision ?? 0) : 0;
        $comisionTotal = round($totalGenerado * ($porcentajeComision / 100), 2);
        $comisionTotalBs = round($totalGeneradoBs * ($porcentajeComision / 100), 2);
        $comisionAsistente = round($citasAsistidas->sum(function ($cita) {
            return (float) $cita->monto_total * ((float) ($cita->comision_asistente_porcentaje ?? 3) / 100);
        }), 2);
        $comisionAsistenteBs = round($citasAsistidas->sum(function ($cita) {
            return (float) $cita->monto_total_bs * ((float) ($cita->comision_asistente_porcentaje ?? 3) / 100);
        }), 2);

        $nombreEspecialista = $usuario ? $usuario->name : 'Especialista';
        $especialidad = ($usuario && $usuario->role === 'especialista') ? 'Especialista' : 'Administrador';

        return [
            'especialista' => [
                'nombre' => $nombreEspecialista,
                'especialidad' => $especialidad,
                'comision' => $porcentajeComision,
            ],
            'total_generado' => $totalGenerado,
            'total_generado_bs' => $totalGeneradoBs,
            'comision_total' => $comisionTotal,
            'comision_total_bs' => $comisionTotalBs,
            'comision_asistente' => $comisionAsistente,
            'comision_asistente_bs' => $comisionAsistenteBs,
            'filters' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
            ],
            'atenciones' => $citas->map(function ($cita) use ($usuarioId) {
                $paciente = $cita->paciente ? $cita->paciente->nombre : null;
                $servicio = $cita->servicios->pluck('nombre')->join(', ');

                return [
                    'fecha' => $cita->fecha ? $cita->fecha->format('d/m/Y') : 'N/A',
                    'paciente' => $paciente ?? 'Paciente no registrado',
                    'servicio' => $servicio ?: 'Atención General',
                    'monto' => (float) $cita->monto_total,
                    'monto_bs' => (float) ($cita->monto_total_bs ?? 0),
                    'es_asistente' => (int) $cita->asistente_id === (int) $usuarioId && (int) $cita->user_id !== (int) $usuarioId,
                    'especialista' => $cita->especialista?->name,
                    'comision_asistente' => $cita->asistente_id && (int) $cita->asistente_id === (int) $usuarioId
                        ? round((float) $cita->monto_total * ((float) ($cita->comision_asistente_porcentaje ?? 3) / 100), 2)
                        : 0,
                    'comision_asistente_bs' => $cita->asistente_id && (int) $cita->asistente_id === (int) $usuarioId
                        ? round((float) $cita->monto_total_bs * ((float) ($cita->comision_asistente_porcentaje ?? 3) / 100), 2)
                        : 0,
                ];
            })->values()->all(),
        ];
    }
}
