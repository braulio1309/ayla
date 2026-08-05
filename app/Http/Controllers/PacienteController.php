<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Paciente;

class PacienteController extends Controller
{
    public function index()
    {
        return Inertia::render('Pacientes', [
            'pacientes' => [
                [
                    'id' => 1,
                    'nombre' => 'María Rivas',
                    'cedula' => 'V-18.234.567',
                    'telefono' => '+58 414-1234567',
                    'email' => 'maria.rivas@email.com',
                    'citas_count' => 4,
                    'historial' => [
                        ['fecha' => '04/08/2026', 'servicio' => 'Limpieza Facial Profunda', 'especialista' => 'Dra. Elena Gómez', 'monto' => 25.00],
                        ['fecha' => '12/07/2026', 'servicio' => 'Manicura Rusa', 'especialista' => 'Lcda. Sofia Torres', 'monto' => 15.00],
                    ]
                ],
                [
                    'id' => 2,
                    'nombre' => 'Carlos Mendoza',
                    'cedula' => 'V-12.890.123',
                    'telefono' => '+58 412-9876543',
                    'email' => 'carlos.mendoza@email.com',
                    'citas_count' => 2,
                    'historial' => [
                        ['fecha' => '03/08/2026', 'servicio' => 'Masaje Relajante', 'especialista' => 'Dra. Maria Perez', 'monto' => 45.00]
                    ]
                ]
            ]
        ]);
    }

    public function store(Request $request)
    {
        return redirect()->back()->with('success', 'Paciente registrado correctamente.');
    }
}