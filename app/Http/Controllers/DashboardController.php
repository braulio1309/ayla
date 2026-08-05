<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard', [
            'kpis' => [
                'ingresos_mes' => 1280.00,
                'turnos_hoy' => 8,
                'pacientes_totales' => Paciente::count() ?: 142,
                'especialistas_activos' => User::where('role', 'especialista')->count() ?: 5,
            ],
            'citas_hoy' => [
                ['hora' => '08:00 AM', 'paciente' => 'María Rivas', 'servicio' => 'Limpieza Facial Profunda', 'especialista' => 'Dra. Elena Gómez', 'monto' => 25.00, 'estado' => 'Completado'],
                ['hora' => '09:30 AM', 'paciente' => 'Carlos Mendoza', 'servicio' => 'Masaje Relajante (90 min)', 'especialista' => 'Dra. Maria Perez', 'monto' => 45.00, 'estado' => 'En Proceso'],
                ['hora' => '11:00 AM', 'paciente' => 'Ana López', 'servicio' => 'Manicura Rusa + Pedicura', 'especialista' => 'Lcda. Sofia Torres', 'monto' => 30.00, 'estado' => 'Confirmado'],
            ]
        ]);
    }
}