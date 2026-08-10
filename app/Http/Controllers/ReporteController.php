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
            'especialistas_lista' => $data['especialistas_lista'],
            'servicios_lista' => $data['servicios_lista'],
        ]);
    }
}