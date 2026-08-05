<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\User;

class UsuarioController extends Controller
{
    public function index()
    {
        return Inertia::render('Usuarios', [
            'usuarios' => [
                ['id' => 1, 'name' => 'Braulio Zapata', 'email' => 'braulio.admin@ayla.com', 'role' => 'admin', 'status' => 'Activo'],
                ['id' => 2, 'name' => 'Dra. Elena Gómez', 'email' => 'elena.gomez@ayla.com', 'role' => 'especialista', 'status' => 'Activo'],
                ['id' => 3, 'name' => 'Dra. Maria Perez', 'email' => 'maria.perez@ayla.com', 'role' => 'especialista', 'status' => 'Activo'],
            ]
        ]);
    }

    public function especialistaPanel()
    {
        return Inertia::render('PanelEspecialista', [
            'especialista' => ['nombre' => 'Dra. Elena Gómez', 'especialidad' => 'Cosmiatría'],
            'comision_total' => 450.00,
            'atenciones' => [
                ['fecha' => '04/08/2026', 'paciente' => 'María Rivas', 'servicio' => 'Limpieza Facial Profunda', 'monto' => 25.00],
                ['fecha' => '03/08/2026', 'paciente' => 'Lucia Fernández', 'servicio' => 'Tratamiento Antiage + Peeling', 'monto' => 50.00],
            ]
        ]);
    }
}