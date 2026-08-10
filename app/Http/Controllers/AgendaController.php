<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Services\AgendaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    protected $agendaService;

    public function __construct(AgendaService $agendaService)
    {
        $this->agendaService = $agendaService;
    }

    public function index(Request $request)
    {
        $fecha = $request->input('fecha', date('Y-m-d'));
        $especialistaId = $request->input('especialista_id');
        $estado = $request->input('estado');

        $data = $this->agendaService->getAgendaData($fecha, $especialistaId ? (int) $especialistaId : null, $estado);

        return Inertia::render('Agenda', [
            'filters' => $data['filters'],
            'turnos' => $data['turnos'],
            'calendario' => $data['calendario'],
            'pacientes_lista' => $data['pacientes_lista'],
            'servicios_lista' => $data['servicios_lista'],
            'especialistas_lista' => $data['especialistas_lista'],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'paciente_id' => 'required|exists:pacientes,id',
            'especialista_id' => 'required|exists:users,id',
            'servicios' => 'required|array|min:1',
            'fecha' => 'required|date',
            'hora_inicio' => 'required',
            'holgura_min' => 'required|numeric'
        ]);

        $paciente = Paciente::findOrFail($validated['paciente_id']);
        $serviciosSeleccionados = Servicio::whereIn('id', $validated['servicios'])->get();

        if ($serviciosSeleccionados->isEmpty()) {
            return redirect()->back()->withErrors(['servicios' => 'Debe seleccionar al menos un servicio.']);
        }

        $especialistaId = Auth::check() && Auth::user()->role === 'especialista'
            ? Auth::id()
            : $validated['especialista_id'];
        $validated['especialista_id'] = $especialistaId;

        $duracionTotal = $serviciosSeleccionados->sum(function ($servicio) {
            return (int) ($servicio->duracion_min ?? 0);
        });

        $conflicto = $this->buscarConflicto($validated['paciente_id'], $validated['especialista_id'], $validated['fecha'], $request->hora_inicio, $validated['holgura_min'], $duracionTotal);
        if ($conflicto) {
            return redirect()->back()->withErrors(['disponibilidad' => 'No se puede agendar porque ' . $conflicto])->withInput();
        }

        $precioTotal = $serviciosSeleccionados->sum(function ($servicio) {
            return (float) ($servicio->precio_base ?? 0);
        });

        $horaInicio = Carbon::createFromFormat('H:i', $request->hora_inicio);
        $horaFin = $horaInicio->copy()->addMinutes($duracionTotal + (int) $request->holgura_min);

        $cita = Cita::create([
            'paciente_id' => $paciente->id,
            'user_id' => $validated['especialista_id'],
            'fecha' => $validated['fecha'],
            'hora_inicio' => $horaInicio->format('H:i:s'),
            'hora_fin' => $horaFin->format('H:i:s'),
            'holgura_min' => (int) $validated['holgura_min'],
            'monto_total' => $precioTotal,
            'estado' => 'confirmado',
            'cabina' => null,
            'observaciones' => 'Cita creada desde la agenda',
        ]);

        foreach ($serviciosSeleccionados as $servicio) {
            $cita->servicios()->attach($servicio->id, [
                'precio_momento' => (float) ($servicio->precio_base ?? 0),
                'duracion_momento' => (int) ($servicio->duracion_min ?? 0),
            ]);
        }

        return redirect()->route('agenda.index', [
            'fecha' => $validated['fecha'],
            'especialista_id' => $validated['especialista_id'],
        ])->with('success', 'Turno agendado exitosamente.');
    }

    private function buscarConflicto($pacienteId, $especialistaId, $fecha, $horaInicio, $holguraMin, $duracionTotal)
    {
        $horaInicioDate = Carbon::createFromFormat('H:i', $horaInicio);
        $horaFinDate = $horaInicioDate->copy()->addMinutes($duracionTotal + (int) $holguraMin);

        $citas = Cita::whereDate('fecha', $fecha)
            ->where(function ($query) use ($pacienteId, $especialistaId) {
                $query->where('paciente_id', $pacienteId)
                    ->orWhere('user_id', $especialistaId);
            })
            ->get();

        foreach ($citas as $cita) {
            $citaInicio = Carbon::parse($cita->hora_inicio);
            $citaFin = Carbon::parse($cita->hora_fin ?? $citaInicio->copy()->addMinutes((int) ($cita->holgura_min ?? 0)));

            if ($horaInicioDate->lt($citaFin) && $horaFinDate->gt($citaInicio)) {
                $pacienteNombre = $cita->paciente ? $cita->paciente->nombre : 'este paciente';
                $especialistaNombre = $cita->especialista ? $cita->especialista->name : 'este especialista';

                if ($cita->paciente_id == $pacienteId) {
                    return 'el paciente ' . $pacienteNombre . ' ya tiene un turno agendado para ese horario.';
                }

                if ($cita->user_id == $especialistaId) {
                    return 'el especialista ' . $especialistaNombre . ' ya tiene un turno agendado para ese horario.';
                }
            }
        }

        return null;
    }
}