<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\User;

class DashboardService
{
    public function getDashboardData(): array
    {
        $hoy = now()->format('Y-m-d');

        $citasHoy = Cita::with(['paciente', 'servicios', 'especialista'])
            ->whereDate('fecha', $hoy)
            ->orderBy('hora_inicio')
            ->get()
            ->map(function ($cita) {
                return [
                    'id' => $cita->id,
                    'hora' => $cita->hora_inicio ? \Carbon\Carbon::parse($cita->hora_inicio)->format('h:i A') : 'N/A',
                    'paciente' => ($cita->paciente ? $cita->paciente->nombre : null) ?? 'Sin paciente',
                    'servicio' => $cita->servicios->pluck('nombre')->join(', ') ?: 'Sin servicio',
                    'especialista' => ($cita->especialista ? $cita->especialista->name : null) ?? 'Sin especialista',
                    'monto' => (float)($cita->monto_total ?? 0),
                    'duracion_min' => (int)($cita->duracion_total ?? 0),
                    'estado' => $cita->estado ?? 'Confirmado',
                    'cabina' => $cita->cabina ?? 'Sin cabina',
                    'observaciones' => $cita->observaciones ?? '',
                ];
            })
            ->values()
            ->all();

        return [
            'kpis' => [
                'ingresos_mes' => (float) Cita::whereMonth('fecha', now()->month)->sum('monto_total'),
                'turnos_hoy' => Cita::whereDate('fecha', $hoy)->count(),
                'pacientes_totales' => Paciente::count(),
                'especialistas_activos' => User::where('role', 'especialista')->count(),
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
