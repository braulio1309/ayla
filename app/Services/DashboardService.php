<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\User;
use App\Services\TasaCambioService;

class DashboardService
{
    public function __construct(private TasaCambioService $tasaCambioService)
    {
    }

    public function getDashboardData(?string $fecha = null, ?int $especialistaId = null): array
    {
        $fecha = $fecha ?: now()->format('Y-m-d');

        $query = Cita::with(['paciente', 'servicios', 'especialista', 'asistente'])
            ->whereDate('fecha', $fecha);

        if ($especialistaId) {
            $query->where(function ($q) use ($especialistaId) {
                $q->where('user_id', $especialistaId)
                  ->orWhere('asistente_id', $especialistaId);
            });
        }

        $citasHoy = $query->orderBy('hora_inicio')->get()->map(function ($cita) {
            return [
                'id' => $cita->id,
                'hora' => $cita->hora_inicio ? \Carbon\Carbon::parse($cita->hora_inicio)->format('h:i A') : 'N/A',
                'paciente' => ($cita->paciente ? $cita->paciente->nombre : null) ?? 'Sin paciente',
                'servicio' => $cita->servicios->pluck('nombre')->join(', ') ?: 'Sin servicio',
                'especialista' => ($cita->especialista ? $cita->especialista->name : null) ?? 'Sin especialista',
                'asistente' => $cita->asistente?->name,
                'monto' => (float)($cita->monto_total ?? 0),
                'monto_bs' => (float)($cita->monto_total_bs ?? 0),
                'duracion_min' => (int)($cita->duracion_total ?? 0),
                'estado' => $cita->estado ?? 'Confirmado',
                'cabina' => $cita->cabina ?? 'Sin cabina',
                'observaciones' => $cita->observaciones ?? '',
            ];
        })->values()->all();

        return [
            'tasas' => $this->tasaCambioService->obtener(),
            'kpis' => [
                'ingresos_mes' => (float) Cita::whereMonth('fecha', now()->month)->sum('monto_total'),
                'ingresos_mes_bs' => (float) Cita::whereMonth('fecha', now()->month)->sum('monto_total_bs'),
                'turnos_hoy' => $query->count(),
                'pacientes_totales' => Paciente::count(),
                'especialistas_activos' => User::where('role', 'especialista')->count(),
            ],
            'filters' => [
                'fecha' => $fecha,
                'especialista_id' => $especialistaId,
            ],
            'citas_hoy' => $citasHoy,
            'pacientes_lista' => Paciente::select('id', 'nombre', 'cedula')->get()->map(fn ($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'cedula' => $p->cedula,
            ])->all(),
            'servicios_lista' => Servicio::select('id', 'nombre', 'precio_base', 'duracion_min')->get()->map(fn ($s) => [
                'id' => $s->id,
                'nombre' => $s->nombre,
                'precio' => (float) ($s->precio_base ?? 0),
                'duracion' => (int) ($s->duracion_min ?? 0),
            ])->all(),
            'especialistas_lista' => User::where('role', 'especialista')->select('id', 'name')->get()->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
            ])->all(),
        ];
    }
}
