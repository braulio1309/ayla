<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Inertia\Inertia;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index()
    {
        $data = $this->dashboardService->getDashboardData();

        return Inertia::render('Dashboard', [
            'kpis' => $data['kpis'],
            'citas_hoy' => $data['citas_hoy'],
            'pacientes_lista' => $data['pacientes_lista'],
            'servicios_lista' => $data['servicios_lista'],
            'especialistas_lista' => $data['especialistas_lista'],
        ]);
    }
}