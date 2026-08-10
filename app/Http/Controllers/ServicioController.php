<?php

namespace App\Http\Controllers;

use App\Services\ServicioService;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Servicio;

class ServicioController extends Controller
{
    protected $servicioService;

    public function __construct(ServicioService $servicioService)
    {
        $this->servicioService = $servicioService;
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $categoria = $request->input('categoria');

        $data = $this->servicioService->getServiciosData($search, $categoria);

        return Inertia::render('Servicios', [
            'filters' => $data['filters'],
            'servicios' => $data['servicios'],
            'categorias' => $data['categorias'],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'categoria' => 'required|string|max:100',
            'precio_base' => 'required|numeric|min:0',
            'duracion_min' => 'required|integer|min:5',
            'descripcion' => 'nullable|string'
        ]);

        Servicio::create($validated);

        return redirect()->back()->with('success', 'Servicio registrado correctamente.');
    }

    public function update(Request $request, Servicio $servicio)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'categoria' => 'required|string|max:100',
            'precio_base' => 'required|numeric|min:0',
            'duracion_min' => 'required|integer|min:5',
            'descripcion' => 'nullable|string'
        ]);

        $servicio->update($validated);

        return redirect()->back()->with('success', 'Servicio actualizado correctamente.');
    }
}