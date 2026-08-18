<?php

namespace App\Http\Controllers;

use App\Services\ServicioService;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

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
            'especialistas' => $data['especialistas'],
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(Auth::check() && Auth::user()->role === 'admin', 403);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'categoria' => 'required|string|max:100',
            'precio_base' => 'required|numeric|min:0',
            'duracion_min' => 'required|integer|min:5',
            'descripcion' => 'nullable|string',
            'especialistas' => 'nullable|array',
            'especialistas.*' => 'exists:users,id',
            'precios_especialistas' => 'nullable|array',
            'precios_especialistas.*' => 'nullable|numeric|min:0',
        ]);

        $servicio = Servicio::create([
            'nombre' => $validated['nombre'],
            'categoria' => $validated['categoria'],
            'precio_base' => $validated['precio_base'],
            'duracion_min' => $validated['duracion_min'],
            'descripcion' => $validated['descripcion'] ?? null,
        ]);

        $syncData = [];
        foreach ($request->input('especialistas', []) as $especialistaId) {
            $syncData[(int) $especialistaId] = [
                'precio_especialista' => (float) ($request->input('precios_especialistas.' . $especialistaId, $validated['precio_base'])),
            ];
        }

        if (!empty($syncData)) {
            $servicio->especialistas()->sync($syncData);
        }

        return redirect()->back()->with('success', 'Servicio registrado correctamente.');
    }

    public function update(Request $request, Servicio $servicio)
    {
        abort_unless(Auth::check() && Auth::user()->role === 'admin', 403);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'categoria' => 'required|string|max:100',
            'precio_base' => 'required|numeric|min:0',
            'duracion_min' => 'required|integer|min:5',
            'descripcion' => 'nullable|string',
            'especialistas' => 'nullable|array',
            'especialistas.*' => 'exists:users,id',
            'precios_especialistas' => 'nullable|array',
            'precios_especialistas.*' => 'nullable|numeric|min:0',
        ]);

        $servicio->update([
            'nombre' => $validated['nombre'],
            'categoria' => $validated['categoria'],
            'precio_base' => $validated['precio_base'],
            'duracion_min' => $validated['duracion_min'],
            'descripcion' => $validated['descripcion'] ?? null,
        ]);

        $syncData = [];
        foreach ($request->input('especialistas', []) as $especialistaId) {
            $syncData[(int) $especialistaId] = [
                'precio_especialista' => (float) ($request->input('precios_especialistas.' . $especialistaId, $validated['precio_base'])),
            ];
        }

        $servicio->especialistas()->sync($syncData);

        return redirect()->back()->with('success', 'Servicio actualizado correctamente.');
    }
}