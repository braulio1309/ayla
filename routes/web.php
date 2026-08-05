<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ReporteController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;



// Rutas protegidas (Requieren autenticación)
Route::middleware([])->group(function () {
    
    // Dashboard principal
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Módulo de Agenda y Turnos
    Route::get('/agenda', [AgendaController::class, 'index'])->name('agenda.index');
    Route::post('/agenda', [AgendaController::class, 'store'])->name('agenda.store');

    // Módulo de Pacientes
    Route::get('/pacientes', [PacienteController::class, 'index'])->name('pacientes.index');
    Route::post('/pacientes', [PacienteController::class, 'store'])->name('pacientes.store');

    // Módulo de Servicios
    Route::get('/servicios', [ServicioController::class, 'index'])->name('servicios.index');
    Route::post('/servicios', [ServicioController::class, 'store'])->name('servicios.store');

    // Módulo de Usuarios y Especialistas
    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::get('/panel-especialista', [UsuarioController::class, 'especialistaPanel'])->name('especialista.panel');

    // Módulo de Reportes
    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');

    // Perfil (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Carga automática de autenticación de Breeze (login, register, logout, etc.)
require __DIR__.'/auth.php';