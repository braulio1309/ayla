<?php

namespace App\Http\Controllers;

use App\Services\PacienteService;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Paciente;

class PacienteController extends Controller
{
    protected $pacienteService;

    public function __construct(PacienteService $pacienteService)
    {
        $this->pacienteService = $pacienteService;
    }

    public function index(Request $request)
    {
        abort_unless(auth()->user()?->role === 'admin', 403, 'No tienes permisos para ver pacientes.');

        $search = $request->input('search');
        $data = $this->pacienteService->getPacientesData($search);

        return Inertia::render('Pacientes', [
            'filters' => $data['filters'],
            'pacientes' => $data['pacientes'],
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->role === 'admin', 403, 'No tienes permisos para registrar pacientes.');

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'cedula' => 'required|string|max:50|unique:pacientes,cedula',
            'telefono' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'notas' => 'nullable|string'
        ]);

        Paciente::create($validated);

        return redirect()->back()->with('success', 'Paciente registrado correctamente.');
    }

    public function update(Request $request, Paciente $paciente)
    {
        abort_unless(auth()->user()?->role === 'admin', 403, 'No tienes permisos para editar pacientes.');

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'cedula' => 'required|string|max:50|unique:pacientes,cedula,' . $paciente->id,
            'telefono' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'notas' => 'nullable|string'
        ]);

        $paciente->update($validated);

        return redirect()->back()->with('success', 'Paciente actualizado correctamente.');
    }
}