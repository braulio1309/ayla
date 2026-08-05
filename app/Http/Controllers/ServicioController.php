<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Servicio;

class ServicioController extends Controller
{
    public function index()
    {
        return Inertia::render('Servicios', [
            'servicios' => [
                ['id' => 1, 'nombre' => 'Limpieza Facial Profunda', 'categoria' => 'Cosmiatría', 'precio' => 25.00, 'duracion_min' => 60, 'descripcion' => 'Exfoliación, vapor de ozono e hidratación.'],
                ['id' => 2, 'nombre' => 'Masaje Relajante con Piedras', 'categoria' => 'Masajes & Spa', 'precio' => 45.00, 'duracion_min' => 90, 'descripcion' => 'Terapia corporal completa con piedras volcánicas.'],
                ['id' => 3, 'nombre' => 'Manicura Rusa Tradicional', 'categoria' => 'Manos / Pies', 'precio' => 15.00, 'duracion_min' => 45, 'descripcion' => 'Limpieza de cutículas con torno y esmaltado.']
            ]
        ]);
    }

    public function store(Request $request)
    {
        return redirect()->back()->with('success', 'Servicio guardado exitosamente.');
    }
}