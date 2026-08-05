<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function index()
    {
        return Inertia::render('Reportes', [
            'ingresos_brutos' => 1280.00,
            'citas_completadas' => 64,
            'desglose_especialistas' => [
                ['nombre' => 'Dra. Elena Gómez', 'categoria' => 'Cosmiatría', 'citas' => 28, 'monto' => 580.00, 'porcentaje' => 45.3],
                ['nombre' => 'Dra. Maria Perez', 'categoria' => 'Masajes & Spa', 'citas' => 20, 'monto' => 420.00, 'porcentaje' => 32.8],
                ['nombre' => 'Lcda. Sofia Torres', 'categoria' => 'Estética / Manos', 'citas' => 16, 'monto' => 280.00, 'porcentaje' => 21.9],
            ]
        ]);
    }
}