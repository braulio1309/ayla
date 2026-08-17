<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaStatusUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_especialista_can_update_estado_and_guardar_nota_en_cita(): void
    {
        $especialista = User::factory()->create([
            'role' => 'especialista',
        ]);

        $paciente = Paciente::create([
            'nombre' => 'Ana López',
            'cedula' => '12345678',
            'telefono' => '0999999999',
            'email' => 'ana@example.com',
            'notas' => 'Paciente activa',
        ]);

        $cita = Cita::create([
            'paciente_id' => $paciente->id,
            'user_id' => $especialista->id,
            'fecha' => '2026-08-12',
            'hora_inicio' => '09:00:00',
            'hora_fin' => '09:30:00',
            'holgura_min' => 15,
            'monto_total' => 150.00,
            'estado' => 'confirmado',
            'cabina' => '1',
            'observaciones' => 'Cita creada desde la agenda',
        ]);

        $response = $this->actingAs($especialista)->put('/agenda/' . $cita->id, [
            'estado' => 'en_proceso',
            'observaciones' => 'Paciente llegó puntual y se realizó valoración inicial.',
        ]);

        $response->assertRedirect('/agenda?fecha=2026-08-12&especialista_id=' . $especialista->id);

        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'estado' => 'en_proceso',
            'observaciones' => 'Paciente llegó puntual y se realizó valoración inicial.',
        ]);
    }
}
