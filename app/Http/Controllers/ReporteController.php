<?php

namespace App\Http\Controllers;

use App\Services\ReporteService;
use Inertia\Inertia;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    protected $reporteService;

    public function __construct(ReporteService $reporteService)
    {
        $this->reporteService = $reporteService;
    }

    public function index(Request $request)
    {
        $periodo = $request->input('periodo', 'agosto_2026');
        $especialistaId = $request->input('especialista_id', '');
        $servicioId = $request->input('servicio_id', '');

        $data = $this->reporteService->getReporteData($periodo, $especialistaId ? (int) $especialistaId : null, $servicioId ? (int) $servicioId : null);

        return Inertia::render('Reportes', [
            'filters' => $data['filters'],
            'kpis' => $data['kpis'],
            'auditoria_especialistas' => $data['auditoria_especialistas'],
            'agendas' => $data['agendas'],
            'especialistas_lista' => $data['especialistas_lista'],
            'servicios_lista' => $data['servicios_lista'],
        ]);
    }

    public function exportar(Request $request)
    {
        $especialistaId = $request->integer('especialista_id') ?: null;
        $servicioId = $request->integer('servicio_id') ?: null;
        $data = $this->reporteService->getReporteData(
            $request->input('periodo', 'agosto_2026'),
            $especialistaId,
            $servicioId
        );

        return response()->streamDownload(function () use ($data) {
            echo "\xEF\xBB\xBF";
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Fecha', 'Hora', 'Paciente', 'Servicio', 'Estado', 'Monto USD', 'Monto Bs'], ';');

            foreach ($data['agendas'] as $agenda) {
                fputcsv($handle, [
                    $agenda['fecha'],
                    $agenda['hora'],
                    $agenda['paciente'],
                    $agenda['servicio'],
                    $agenda['estado'],
                    number_format($agenda['monto'], 2, ',', ''),
                    number_format($agenda['monto_bs'], 2, ',', ''),
                ], ';');
            }

            fclose($handle);
        }, 'agendas-finanzas.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}