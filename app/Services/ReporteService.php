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
            'especialista' => function ($query) {
                $query->select('id', 'name', 'role');
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

        $auditoria = $citas->groupBy('user_id')->map(function ($items, $especialistaId) use ($totalGeneral) {
            $especialista = null;
            if ($items->isNotEmpty() && $items->first()->especialista) {
                $especialista = $items->first()->especialista;
            }

            $total = $items->sum('monto_total');
            $porcentaje = $totalGeneral > 0 ? round(($total / $totalGeneral) * 100, 1) : 0;
            $categoria = 'Sin categoría';

            if ($especialista) {
                $categoria = $especialista->role === 'especialista' ? 'Especialista' : 'Administrador';
            }

            return [
                'especialista' => $especialista ? $especialista->name : 'Sin especialista',
                'categoria' => $categoria,
                'citas_completadas' => $items->count(),
                'ingreso_generado' => (float) $total,
                'aporte_porcentaje' => $porcentaje . '%',
            ];
        })->values()->all();

        return [
            'filters' => [
                'periodo' => $periodo,
                'especialista_id' => $especialistaId,
                'servicio_id' => $servicioId,
            ],
            'kpis' => [
                'ingresos_brutos' => (float) $citas->sum('monto_total'),
                'total_citas' => $citas->count(),
                'promedio_cita' => $citas->count() > 0 ? round($citas->sum('monto_total') / $citas->count(), 2) : 0,
                'top_especialista' => $auditoria[0]['especialista'] ?? 'Sin datos',
                'top_especialista_monto' => $auditoria[0]['ingreso_generado'] ?? 0,
                'top_especialista_porcentaje' => $auditoria[0]['aporte_porcentaje'] ?? '0%',
            ],
            'auditoria_especialistas' => $auditoria,
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
