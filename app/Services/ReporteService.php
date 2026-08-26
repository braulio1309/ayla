<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\Servicio;
use App\Models\User;

class ReporteService
{
    public function getReporteData(?string $periodo = null, ?int $especialistaId = null, ?int $servicioId = null): array
    {
        $periodo = $periodo ?: 'agosto_2026';

        $citas = Cita::with([
            'paciente' => function ($query) {
                $query->select('id', 'nombre');
            },
            'especialista' => function ($query) {
                $query->select('id', 'name', 'role', 'comision');
            },
            'servicios' => function ($query) {
                $query->select('servicios.id', 'servicios.nombre');
            },
        ]);

        if ($especialistaId) {
            $citas->where('user_id', $especialistaId);
        }

        if ($servicioId) {
            $citas->whereHas('servicios', function ($query) use ($servicioId) {
                $query->where('servicios.id', $servicioId);
            });
        }

        $citas = $citas->get();
        $totalGeneral = (float) $citas->sum('monto_total');
        $totalGeneralBs = (float) $citas->sum('monto_total_bs');

        $auditoria = $citas->groupBy('user_id')->map(function ($items) use ($totalGeneral) {
            $especialista = null;
            if ($items->isNotEmpty() && $items->first()->especialista) {
                $especialista = $items->first()->especialista;
            }

            $total = (float) $items->sum('monto_total');
            $totalBs = (float) $items->sum('monto_total_bs');
            $porcentajeComision = $especialista && $especialista->role === 'especialista' ? (float) ($especialista->comision ?? 0) : 0;
            $comisionEspecialista = round($total * ($porcentajeComision / 100), 2);
            $negocio = round($total - $comisionEspecialista, 2);
            $porcentaje = $totalGeneral > 0 ? round(($total / $totalGeneral) * 100, 1) : 0;
            $categoria = 'Sin categoría';

            if ($especialista) {
                $categoria = $especialista->role === 'especialista' ? 'Especialista' : 'Administrador';
            }

            return [
                'especialista' => $especialista ? $especialista->name : 'Sin especialista',
                'categoria' => $categoria,
                'citas_completadas' => $items->count(),
                'ingreso_generado' => round($total, 2),
                'ingreso_generado_bs' => round($totalBs, 2),
                'comision_especialista' => $comisionEspecialista,
                'ganancia_negocio' => $negocio,
                'aporte_porcentaje' => $porcentaje . '%',
            ];
        })->values()->all();

        $totalComisionEspecialistas = round(array_sum(array_column($auditoria, 'comision_especialista')), 2);
        $totalNegocio = round(array_sum(array_column($auditoria, 'ganancia_negocio')), 2);

        $agendas = $citas->sortByDesc('fecha')->values()->map(function ($cita) {
            return [
                'id' => $cita->id,
                'fecha' => $cita->fecha?->format('d/m/Y'),
                'hora' => $cita->hora_inicio ? substr((string) $cita->hora_inicio, 0, 5) : 'N/A',
                'paciente' => $cita->paciente?->nombre ?? 'Sin paciente',
                'servicio' => $cita->servicios->pluck('nombre')->join(', ') ?: 'Sin servicio',
                'estado' => $cita->estado,
                'monto' => (float) ($cita->monto_total ?? 0),
                'monto_bs' => (float) ($cita->monto_total_bs ?? 0),
            ];
        })->all();

        return [
            'filters' => [
                'periodo' => $periodo,
                'especialista_id' => $especialistaId,
                'servicio_id' => $servicioId,
            ],
            'kpis' => [
                'ingresos_brutos' => (float) $citas->sum('monto_total'),
                'ingresos_brutos_bs' => $totalGeneralBs,
                'total_comision_especialistas' => $totalComisionEspecialistas,
                'total_negocio' => $totalNegocio,
                'total_citas' => $citas->count(),
                'promedio_cita' => $citas->count() > 0 ? round($citas->sum('monto_total') / $citas->count(), 2) : 0,
                'top_especialista' => $auditoria[0]['especialista'] ?? 'Sin datos',
                'top_especialista_monto' => $auditoria[0]['ingreso_generado'] ?? 0,
                'top_especialista_porcentaje' => $auditoria[0]['aporte_porcentaje'] ?? '0%',
            ],
            'auditoria_especialistas' => $auditoria,
            'agendas' => $agendas,
            'especialistas_lista' => User::where('role', 'especialista')->select('id', 'name')->get()->map(fn ($u) => [
                'id' => $u->id,
                'nombre' => $u->name,
            ])->all(),
            'servicios_lista' => Servicio::select('id', 'nombre')->get()->map(fn ($s) => [
                'id' => $s->id,
                'nombre' => $s->nombre,
            ])->all(),
        ];
    }
}
