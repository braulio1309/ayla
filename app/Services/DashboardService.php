<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\Paciente;
use App\Models\User;
use App\Services\TasaCambioService;
use App\Services\AgendaService;

class DashboardService
{
    public function __construct(
        private TasaCambioService $tasaCambioService,
        private AgendaService $agendaService
    ) {
    }

    public function getDashboardData(?string $fecha = null, ?int $especialistaId = null): array
    {
        $fecha = $fecha ?: now()->format('Y-m-d');
        $agendaData = $this->agendaService->getAgendaData($fecha, $especialistaId);

        return [
            'tasas' => $this->tasaCambioService->obtener(),
            'kpis' => [
                'ingresos_mes' => (float) Cita::whereMonth('fecha', now()->month)->sum('monto_total'),
                'ingresos_mes_bs' => (float) Cita::whereMonth('fecha', now()->month)->sum('monto_total_bs'),
                'turnos_hoy' => count($agendaData['turnos']),
                'pacientes_totales' => Paciente::count(),
                'especialistas_activos' => User::where('role', 'especialista')->count(),
            ],
            'filters' => [
                'fecha' => $fecha,
                'especialista_id' => $especialistaId,
            ],
            'citas_hoy' => $agendaData['turnos'],
            'pacientes_lista' => $agendaData['pacientes_lista'],
            'servicios_lista' => $agendaData['servicios_lista'],
            'especialistas_lista' => $agendaData['especialistas_lista'],
        ];
    }
}
