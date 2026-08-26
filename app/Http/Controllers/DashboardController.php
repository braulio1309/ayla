<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Inertia\Inertia;
use App\Models\TasaCambio;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(Request $request)
    {
        $fecha = $request->input('fecha', now()->format('Y-m-d'));
        $especialistaId = $request->input('especialista_id');
        $data = $this->dashboardService->getDashboardData($fecha, $especialistaId ? (int) $especialistaId : null);

        return Inertia::render('Dashboard', [
            'kpis' => $data['kpis'],
            'filters' => $data['filters'],
            'tasas' => [
                'dolar_bcv' => (float) $data['tasas']->dolar_bcv,
                'euro_bcv' => (float) $data['tasas']->euro_bcv,
                'actualizada_en' => $data['tasas']->actualizada_en?->format('d/m/Y H:i'),
            ],
            'citas_hoy' => $data['citas_hoy'],
            'pacientes_lista' => $data['pacientes_lista'],
            'servicios_lista' => $data['servicios_lista'],
            'especialistas_lista' => $data['especialistas_lista'],
        ]);
    }

    public function actualizarTasas(Request $request)
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $validated = $request->validate([
            'dolar_bcv' => ['required', 'numeric', 'gt:0'],
            'euro_bcv' => ['required', 'numeric', 'gt:0'],
        ]);

        TasaCambio::create([
            ...$validated,
            'actualizada_en' => now(),
        ]);

        return redirect()->route('dashboard')->with('success', 'Tasas BCV actualizadas correctamente.');
    }
}