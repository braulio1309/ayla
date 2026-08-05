<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\User;

class AgendaController extends Controller
{
    public function index()
    {
        return Inertia::render('Agenda', [
            'pacientes' => Paciente::all(),
            'servicios' => Servicio::all(),
            'especialistas' => User::where('role', 'especialista')->get(),
            'turnos' => [
                [
                    'id' => 101,
                    'hora_inicio' => '08:00 AM',
                    'duracion_min' => 60,
                    'paciente' => 'María Rivas',
                    'servicio' => 'Limpieza Facial Profunda',
                    'especialista' => 'Dra. Elena Gómez',
                    'monto' => 25.00,
                    'cabina' => 1,
                    'estado' => 'Completado'
                ],
                [
                    'id' => 102,
                    'hora_inicio' => '09:30 AM',
                    'duracion_min' => 90,
                    'paciente' => 'Carlos Mendoza',
                    'servicio' => 'Masaje Relajante + Piedras',
                    'especialista' => 'Dra. Maria Perez',
                    'monto' => 45.00,
                    'cabina' => 2,
                    'estado' => 'En Proceso'
                ]
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'paciente_id' => 'required',
            'especialista_id' => 'required',
            'servicios' => 'required|array',
            'fecha' => 'required|date',
            'hora_inicio' => 'required',
            'holgura_min' => 'required|numeric'
        ]);

        return redirect()->back()->with('success', 'Turno agendado exitosamente.');
    }
}