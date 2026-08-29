<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ReporteController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;



// Rutas protegidas (Requieren autenticación)
Route::middleware(['auth'])->group(function () {
    Route::get('/', function (Illuminate\Http\Request $request) {
        if (Auth::user()?->role === 'especialista') {
            return redirect()->route('especialista.panel');
        }

        return app(DashboardController::class)->index($request);
    })->name('dashboard');
    Route::post('/tasas-cambio', [DashboardController::class, 'actualizarTasas'])
        ->name('tasas.update');

    // Módulo de Agenda y Turnos
    Route::get('/agenda', [AgendaController::class, 'index'])->name('agenda.index');
    Route::post('/agenda', [AgendaController::class, 'store'])->name('agenda.store');
    Route::put('/agenda/{cita}', [AgendaController::class, 'update'])->name('agenda.update');
    Route::delete('/agenda/{cita}', [AgendaController::class, 'destroy'])->name('agenda.destroy');

    // Módulo de Pacientes
    Route::get('/pacientes', function () {
        abort_unless(Auth::user()?->role === 'admin', 403, 'No tienes permisos para ver pacientes.');

        return app(PacienteController::class)->index(request());
    })->name('pacientes.index');
    Route::post('/pacientes', [PacienteController::class, 'store'])->name('pacientes.store');
    Route::put('/pacientes/{paciente}', [PacienteController::class, 'update'])->name('pacientes.update');

    // Módulo de Servicios
    Route::get('/servicios', [ServicioController::class, 'index'])->name('servicios.index');
    Route::post('/servicios', [ServicioController::class, 'store'])->name('servicios.store');
    Route::put('/servicios/{servicio}', [ServicioController::class, 'update'])->name('servicios.update');
    Route::delete('/servicios/{servicio}', [ServicioController::class, 'destroy'])->name('servicios.destroy');

    // Módulo de Usuarios y Especialistas
    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
    Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
    Route::get('/panel-especialista', [UsuarioController::class, 'especialistaPanel'])->name('especialista.panel');

    // Módulo de Reportes
    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('/reportes/exportar', [ReporteController::class, 'exportar'])->name('reportes.exportar');

    // Perfil (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Carga automática de autenticación de Breeze (login, register, logout, etc.)
require __DIR__.'/auth.php';