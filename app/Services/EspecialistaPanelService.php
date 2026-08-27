<?php

namespace App\Services;

use App\Models\Cita;
use Illuminate\Support\Facades\Auth;

class EspecialistaPanelService
{
    public function getPanelData(?string $fechaInicio = null, ?string $fechaFin = null, ?string $fecha = null): array
    {
        $usuario = Auth::user();
        $fechaInicio = $fechaInicio ?: ($fecha ?: now()->startOfMonth()->format('Y-m-d'));
        $fechaFin = $fechaFin ?: ($fecha ?: now()->endOfMonth()->format('Y-m-d'));

        $usuarioId = $usuario ? $usuario->id : null;

        $citas = Cita::with(['paciente', 'servicios', 'especialista'])
            ->where(function ($query) use ($usuarioId) {
                $query->where('user_id', $usuarioId)
                    ->orWhere('asistente_id', $usuarioId)
                    ->orWhereHas('servicios', function ($q) use ($usuarioId) {
                        $q->where('cita_servicio.especialista_id', $usuarioId);
                    });
            })
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->orderBy('fecha', 'desc')
            ->get();

        $totalGenerado = 0;
        $totalGeneradoBs = 0;
        $comisionTotal = 0;
        $comisionTotalBs = 0;

        foreach ($citas as $cita) {
            foreach ($cita->servicios as $srv) {
                $espId = (int) ($srv->pivot->especialista_id ?: $cita->user_id);
                if ($espId === (int) $usuarioId) {
                    $precioUsd = (float) ($srv->pivot->precio_momento ?? 0);
                    $precioBs = (float) ($srv->pivot->monto_bs_momento ?? 0);
                    $comisionPct = (float) ($srv->pivot->comision_momento ?? ($usuario->comision ?? 0));

                    $totalGenerado += $precioUsd;
                    $totalGeneradoBs += $precioBs;
                    $comisionTotal += round($precioUsd * ($comisionPct / 100), 2);
                    $comisionTotalBs += round($precioBs * ($comisionPct / 100), 2);
                }
            }
        }

        $citasAsistidas = $citas->where('asistente_id', $usuarioId);
        $comisionAsistente = round($citasAsistidas->sum(function ($cita) {
            $subtotal = (float) $cita->servicios->sum('pivot.precio_momento');
            if ($subtotal <= 0) $subtotal = (float) $cita->monto_total;
            return $subtotal * ((float) ($cita->comision_asistente_porcentaje ?? 0) / 100);
        }), 2);

        $comisionAsistenteBs = round($citasAsistidas->sum(function ($cita) {
            $subtotalBs = (float) $cita->servicios->sum('pivot.monto_bs_momento');
            if ($subtotalBs <= 0) $subtotalBs = (float) $cita->monto_total_bs;
            return $subtotalBs * ((float) ($cita->comision_asistente_porcentaje ?? 0) / 100);
        }), 2);

        $nombreEspecialista = $usuario ? $usuario->name : 'Especialista';
        $especialidad = ($usuario && $usuario->role === 'especialista') ? 'Especialista' : 'Administrador';
        $porcentajeComision = $usuario && $usuario->role === 'especialista' ? (float) ($usuario->comision ?? 0) : 0;

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
            'atenciones' => $citas->map(function ($cita) use ($usuarioId, $usuario) {
                $paciente = $cita->paciente ? $cita->paciente->nombre : null;

                $esAsistente = (int) $cita->asistente_id === (int) $usuarioId;
                $pctAsistente = (float) ($cita->comision_asistente_porcentaje ?? 0);

                $subtotalServicios = (float) $cita->servicios->sum('pivot.precio_momento');
                if ($subtotalServicios <= 0) $subtotalServicios = (float) $cita->monto_total;

                $subtotalServiciosBs = (float) $cita->servicios->sum('pivot.monto_bs_momento');
                if ($subtotalServiciosBs <= 0) $subtotalServiciosBs = (float) $cita->monto_total_bs;

                $gananciaAsistente = $esAsistente ? round($subtotalServicios * ($pctAsistente / 100), 2) : 0;
                $gananciaAsistenteBs = $esAsistente ? round($subtotalServiciosBs * ($pctAsistente / 100), 2) : 0;

                $serviciosDetalle = $cita->servicios->map(function ($srv) use ($usuarioId, $usuario, $cita) {
                    $espId = (int) ($srv->pivot->especialista_id ?: $cita->user_id);
                    $montoServicio = (float) ($srv->pivot->precio_momento ?? 0);
                    $montoServicioBs = (float) ($srv->pivot->monto_bs_momento ?? 0);
                    $comisionPct = (float) ($srv->pivot->comision_momento ?? ($usuario->comision ?? 0));

                    $esMiServicio = $espId === (int) $usuarioId;
                    $miGanancia = $esMiServicio ? round($montoServicio * ($comisionPct / 100), 2) : 0;
                    $miGananciaBs = $esMiServicio ? round($montoServicioBs * ($comisionPct / 100), 2) : 0;

                    return [
                        'id' => $srv->id,
                        'nombre' => $srv->nombre,
                        'monto_total' => $montoServicio,
                        'monto_total_bs' => $montoServicioBs,
                        'especialista_id' => $espId,
                        'comision_porcentaje' => $comisionPct,
                        'es_mi_servicio' => $esMiServicio,
                        'mi_ganancia' => $miGanancia,
                        'mi_ganancia_bs' => $miGananciaBs,
                    ];
                })->values()->all();

                $gananciaServicios = array_sum(array_column($serviciosDetalle, 'mi_ganancia'));
                $gananciaServiciosBs = array_sum(array_column($serviciosDetalle, 'mi_ganancia_bs'));

                $miGananciaTotal = round($gananciaServicios + $gananciaAsistente, 2);
                $miGananciaTotalBs = round($gananciaServiciosBs + $gananciaAsistenteBs, 2);

                return [
                    'id' => $cita->id,
                    'fecha' => $cita->fecha ? $cita->fecha->format('d/m/Y') : 'N/A',
                    'paciente' => $paciente ?? 'Paciente no registrado',
                    'servicio' => $cita->servicios->pluck('nombre')->join(', ') ?: 'Atención General',
                    'monto' => (float) $cita->monto_total,
                    'monto_bs' => (float) ($cita->monto_total_bs ?? 0),
                    'es_asistente' => $esAsistente,
                    'es_principal' => (int) $cita->user_id === (int) $usuarioId,
                    'especialista' => $cita->especialista?->name,
                    'comision_asistente_porcentaje' => $pctAsistente,
                    'comision_asistente' => $gananciaAsistente,
                    'comision_asistente_bs' => $gananciaAsistenteBs,
                    'ganancia_asistente' => $gananciaAsistente,
                    'ganancia_asistente_bs' => $gananciaAsistenteBs,
                    'ganancia_servicios' => $gananciaServicios,
                    'ganancia_servicios_bs' => $gananciaServiciosBs,
                    'mi_ganancia_total' => $miGananciaTotal,
                    'mi_ganancia_total_bs' => $miGananciaTotalBs,
                    'servicios_detalle' => $serviciosDetalle,
                ];
            })->values()->all(),
        ];
    }
}
