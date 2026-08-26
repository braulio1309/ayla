<?php

namespace App\Http\Controllers;

use App\Services\EspecialistaPanelService;
use Illuminate\Support\Facades\Auth;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    protected $especialistaPanelService;

    public function __construct(EspecialistaPanelService $especialistaPanelService)
    {
        $this->especialistaPanelService = $especialistaPanelService;
    }
    /**
     * Muestra el panel de administración de Usuarios y Roles
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $role = $request->input('role');

        $query = User::query();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role) {
            $query->where('role', $role);
        }

        $usuarios = $query->orderBy('name')->get()->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role ?? 'especialista',
                'is_active' => (bool)($u->is_active ?? true),
                'comision' => (float)($u->comision ?? 0),
            ];
        });


        return Inertia::render('Usuarios', [
            'filters' => [
                'search' => $search,
                'role' => $role
            ],
            'usuarios' => $usuarios,
            'roles' => [
                ['id' => 'admin', 'nombre' => 'Administrador'],
                ['id' => 'especialista', 'nombre' => 'Especialista / Profesional']
            ]
        ]);
    }

    /**
     * Registra un nuevo usuario en el sistema
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:admin,especialista',
            'is_active' => 'required|boolean',
            'comision' => 'nullable|numeric|min:0|max:100',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['comision'] = isset($validated['comision']) ? (float) $validated['comision'] : 0;

        User::create($validated);

        return redirect()->back()->with('success', 'Usuario creado correctamente. Ya puede iniciar sesión con sus credenciales.');
    }

    /**
     * Actualiza la información o rol de un usuario existente
     */
    public function update(Request $request, User $usuario)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($usuario->id)],
            'password' => 'nullable|string|min:8',
            'role' => 'required|string|in:admin,especialista',
            'is_active' => 'required|boolean',
            'comision' => 'nullable|numeric|min:0|max:100',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['comision'] = isset($validated['comision']) ? (float) $validated['comision'] : 0;

        $usuario->update($validated);

        return redirect()->back()->with('success', 'Usuario actualizado correctamente. Los cambios quedaron guardados.');
    }

    /**
     * Muestra el panel individual del especialista
     */
    public function especialistaPanel(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $data = $this->especialistaPanelService->getPanelData($fechaInicio, $fechaFin);

        return Inertia::render('PanelEspecialista', [
            'especialista' => $data['especialista'],
            'total_generado' => $data['total_generado'] ?? 0,
            'total_generado_bs' => $data['total_generado_bs'] ?? 0,
            'comision_total' => $data['comision_total'] ?? 0,
            'comision_total_bs' => $data['comision_total_bs'] ?? 0,
            'filters' => $data['filters'],
            'atenciones' => $data['atenciones'] ?? [],
        ]);
    }
}