<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\User;
use App\Services\ReporteService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteServiceWeeklyFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_semanal_report_filters_by_current_week_and_counts_assistant_commission(): void
    {
        $especialista = User::factory()->create([
            'name' => 'Dr. Ana',
            'role' => 'especialista',
            'comision' => 15,
        ]);

        $asistente = User::factory()->create([
            'name' => 'Asistente Luis',
            'role' => 'especialista',
            'comision' => 0,
        ]);

        $paciente = Paciente::create([
            'nombre' => 'Paciente Semana',
            'cedula' => 'V-90000000',
            'telefono' => '04125550000',
            'email' => 'semana@example.com',
            'notas' => 'Prueba de finanzas',
        ]);

        $servicio = Servicio::create([
            'nombre' => 'Limpieza Facial',
            'categoria' => 'Cosmiatría',
            'precio_base' => 100,
            'comision_adicional' => 0,
            'duracion_min' => 60,
            'es_recurrente' => true,
            'descripcion' => 'Servicio de prueba',
        ]);

        $fechaEstaSemana = Carbon::now()->startOfWeek(Carbon::MONDAY)->addDays(1)->format('Y-m-d');
        $fechaSemanaPasada = Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');

        $citaActual = Cita::create([
            'paciente_id' => $paciente->id,
            'user_id' => $especialista->id,
            'asistente_id' => $asistente->id,
            'comision_asistente_porcentaje' => 10,
            'fecha' => $fechaEstaSemana,
            'hora_inicio' => '09:00:00',
            'hora_fin' => '10:00:00',
            'holgura_min' => 15,
            'monto_total' => 100,
            'monto_total_bs' => 200,
            'estado' => 'Confirmado',
            'cabina' => '1',
            'observaciones' => 'Prueba semanal',
        ]);

        $citaActual->servicios()->attach($servicio->id, [
            'precio_momento' => 100,
            'monto_bs_momento' => 200,
            'duracion_momento' => 60,
            'especialista_id' => $especialista->id,
            'comision_momento' => 15,
            'comision_tipo' => 'porcentaje',
            'comision_monto' => 15,
        ]);

        $citaVieja = Cita::create([
            'paciente_id' => $paciente->id,
            'user_id' => $especialista->id,
            'asistente_id' => $asistente->id,
            'comision_asistente_porcentaje' => 10,
            'fecha' => $fechaSemanaPasada,
            'hora_inicio' => '11:00:00',
            'hora_fin' => '12:00:00',
            'holgura_min' => 15,
            'monto_total' => 999,
            'monto_total_bs' => 1998,
            'estado' => 'Confirmado',
            'cabina' => '2',
            'observaciones' => 'Prueba fuera de rango',
        ]);

        $citaVieja->servicios()->attach($servicio->id, [
            'precio_momento' => 999,
            'monto_bs_momento' => 1998,
            'duracion_momento' => 60,
            'especialista_id' => $especialista->id,
            'comision_momento' => 15,
            'comision_tipo' => 'porcentaje',
            'comision_monto' => 149.85,
        ]);

        $data = (new ReporteService())->getReporteData('semanal');

        $this->assertSame(100.0, round((float) $data['kpis']['ingresos_brutos'], 2));
        $this->assertSame(10.0, round((float) $data['kpis']['total_comision_asistentes'], 2));
        $this->assertCount(1, $data['agendas']);
        $this->assertSame('Limpieza Facial', $data['agendas'][0]['servicio']);
    }

    public function test_semanal_report_includes_saturday_and_excludes_sunday(): void
    {
        $especialista = User::factory()->create([
            'role' => 'especialista',
            'comision' => 10,
        ]);

        $paciente = Paciente::create([
            'nombre' => 'Paciente Fin de Semana',
            'cedula' => 'V-90000001',
            'telefono' => '04125550001',
        ]);

        $servicio = Servicio::create([
            'nombre' => 'Masaje',
            'categoria' => 'Corporal',
            'precio_base' => 50,
            'duracion_min' => 60,
            'es_recurrente' => true,
        ]);

        $sabado = Carbon::now()->startOfWeek(Carbon::MONDAY)->addDays(5);
        $domingo = $sabado->copy()->addDay();

        foreach ([$sabado, $domingo] as $fecha) {
            $cita = Cita::create([
                'paciente_id' => $paciente->id,
                'user_id' => $especialista->id,
                'fecha' => $fecha->format('Y-m-d'),
                'hora_inicio' => '09:00:00',
                'hora_fin' => '10:00:00',
                'holgura_min' => 15,
                'monto_total' => 50,
                'monto_total_bs' => 100,
                'estado' => 'confirmado',
            ]);

            $cita->servicios()->attach($servicio->id, [
                'precio_momento' => 50,
                'monto_bs_momento' => 100,
                'duracion_momento' => 60,
                'especialista_id' => $especialista->id,
                'comision_momento' => 10,
                'comision_tipo' => 'porcentaje',
                'comision_monto' => 5,
            ]);
        }

        $data = (new ReporteService())->getReporteData('semanal');

        $this->assertSame(50.0, round((float) $data['kpis']['ingresos_brutos'], 2));
        $this->assertCount(1, $data['agendas']);
        $this->assertSame($sabado->format('d/m/Y'), $data['agendas'][0]['fecha']);
    }
}
