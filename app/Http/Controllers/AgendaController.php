<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\User;
use App\Services\AgendaService;
use App\Services\MetaWhatsAppService;
use App\Services\TasaCambioService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Notifications\NuevaCitaAsignada;

class AgendaController extends Controller
{
    protected $agendaService;
    protected $metaWhatsAppService;
    protected $tasaCambioService;

    public function __construct(AgendaService $agendaService, MetaWhatsAppService $metaWhatsAppService, TasaCambioService $tasaCambioService)
    {
        $this->agendaService = $agendaService;
        $this->metaWhatsAppService = $metaWhatsAppService;
        $this->tasaCambioService = $tasaCambioService;
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
            'asistente_id' => 'nullable|exists:users,id|different:especialista_id',
            'comision_asistente' => 'nullable|numeric|min:0|max:100',
            'servicios' => 'required|array|min:1',
            'servicios.*' => 'integer|exists:servicios,id',
            'precios_servicios' => 'nullable|array',
            'precios_servicios.*' => 'nullable|numeric|min:0',
            'servicio_especialistas' => 'nullable|array',
            'servicio_especialistas.*' => 'nullable|exists:users,id',
            'servicio_comisiones' => 'nullable|array',
            'servicio_comisiones.*' => 'nullable|numeric|min:0|max:100',
            'servicio_comision_tipos' => 'nullable|array',
            'servicio_comision_tipos.*' => 'nullable|in:porcentaje,monto',
            'servicio_comision_montos' => 'nullable|array',
            'servicio_comision_montos.*' => 'nullable|numeric|min:0',
            'fecha' => 'required|date',
            'hora_inicio' => 'required',
            'holgura_min' => 'required|numeric',
            'recurrencia' => 'nullable|string|in:ninguna,diario,semanal,quincenal,mensual',
            'dias_semana' => 'nullable|array',
            'dias_semana.*' => 'integer|min:0|max:6',
            'cantidad_sesiones' => 'nullable|integer|min:1|max:60',
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

        if (!empty($validated['asistente_id'])) {
            abort_unless(User::whereKey($validated['asistente_id'])->where('role', 'especialista')->exists(), 422, 'El asistente debe ser un especialista.');
        }

        $duracionTotal = $serviciosSeleccionados->sum(function ($servicio) {
            return (int) ($servicio->duracion_min ?? 0);
        });

        $recurrencia = $validated['recurrencia'] ?? 'ninguna';
        $diasSemana = array_values(array_unique(array_map('intval', $validated['dias_semana'] ?? [])));
        $cantidadSesiones = max(1, (int) ($validated['cantidad_sesiones'] ?? 1));
        $fechasAgenda = $this->generarFechasRecurrencia($validated['fecha'], $recurrencia, $cantidadSesiones, $diasSemana);

        foreach ($fechasAgenda as $fechaCita) {
            $conflicto = $this->buscarConflicto(
                $validated['paciente_id'],
                $validated['especialista_id'],
                $fechaCita,
                $request->hora_inicio,
                $validated['holgura_min'],
                $duracionTotal
            );

            if ($conflicto) {
                return redirect()->back()->withErrors(['disponibilidad' => 'No se puede agendar porque ' . $conflicto])->withInput();
            }
        }

        $subtotalServicios = $serviciosSeleccionados->sum(function ($servicio) use ($validated, $request) {
            $especialistaServicioId = (int) ($request->input('servicio_especialistas.' . $servicio->id, $validated['especialista_id']) ?? $validated['especialista_id']);
            $precioServicio = $servicio->getPrecioParaEspecialista($especialistaServicioId);
            $precioManual = $request->input('precios_servicios.' . $servicio->id);
            $precioFinal = (float) ($precioManual !== null && $precioManual !== '' ? $precioManual : $precioServicio);

            $cantidadSesiones = max(1, (int) ($validated['cantidad_sesiones'] ?? 1));
            $esRecurrente = (bool) ($servicio->es_recurrente ?? true);

            return $esRecurrente ? $precioFinal : $precioFinal * $cantidadSesiones;
        });

        $tasas = $this->tasaCambioService->obtener();
        $comisionAsistente = !empty($validated['asistente_id']) ? (float) ($validated['comision_asistente'] ?? 3) : 0;
        $montoAsistente = !empty($validated['asistente_id']) ? round($subtotalServicios * ($comisionAsistente / 100), 2) : 0;
        $precioTotal = $subtotalServicios + $montoAsistente;
        $montoTotalBs = round($precioTotal * (float) $tasas->euro_bcv, 2);

        $citasCreadas = [];
        foreach ($fechasAgenda as $fechaCita) {
            $fechaCarbon = Carbon::parse($fechaCita);
            $horaInicio = Carbon::parse($fechaCarbon->format('Y-m-d') . ' ' . $request->hora_inicio);
            $horaFin = $horaInicio->copy()->addMinutes($duracionTotal + (int) $validated['holgura_min']);

            $cita = Cita::create([
                'paciente_id' => $paciente->id,
                'user_id' => $validated['especialista_id'],
                'asistente_id' => $validated['asistente_id'] ?? null,
                'comision_asistente_porcentaje' => !empty($validated['asistente_id']) ? $comisionAsistente : null,
                'fecha' => $fechaCarbon->format('Y-m-d'),
                'hora_inicio' => $horaInicio->format('H:i:s'),
                'hora_fin' => $horaFin->format('H:i:s'),
                'holgura_min' => (int) $validated['holgura_min'],
                'monto_total' => $precioTotal,
                'tasa_dolar_bcv' => $tasas->dolar_bcv,
                'tasa_euro_bcv' => $tasas->euro_bcv,
                'monto_total_bs' => $montoTotalBs,
                'estado' => $this->mapEstadoParaBaseDatos('Confirmado'),
                'cabina' => null,
                'observaciones' => count($fechasAgenda) > 1 ? 'Turno recurrente agendado desde la agenda.' : 'Cita creada desde la agenda',
            ]);

            foreach ($serviciosSeleccionados as $servicio) {
                $especialistaServicioId = (int) ($request->input('servicio_especialistas.' . $servicio->id, $validated['especialista_id']) ?? $validated['especialista_id']);
                $precioManual = $request->input('precios_servicios.' . $servicio->id);
                $precioMomento = $precioManual !== null && $precioManual !== ''
                    ? (float) $precioManual
                    : $servicio->getPrecioParaEspecialista($especialistaServicioId);
                $comisionTipo = $request->input('servicio_comision_tipos.' . $servicio->id, 'porcentaje');
                $comisionValor = $comisionTipo === 'monto'
                    ? (float) ($request->input('servicio_comision_montos.' . $servicio->id, 0) ?? 0)
                    : round($precioMomento * ((float) ($request->input('servicio_comisiones.' . $servicio->id, 0) ?? 0) / 100), 2);
                $comisionPorcentaje = $comisionTipo === 'monto' && $precioMomento > 0
                    ? round(($comisionValor / $precioMomento) * 100, 2)
                    : (float) ($request->input('servicio_comisiones.' . $servicio->id, 0) ?? 0);

                $cita->servicios()->attach($servicio->id, [
                    'precio_momento' => $precioMomento,
                    'monto_bs_momento' => round($precioMomento * (float) $tasas->euro_bcv, 2),
                    'duracion_momento' => (int) ($servicio->duracion_min ?? 0),
                    'especialista_id' => $especialistaServicioId,
                    'comision_momento' => $comisionPorcentaje,
                    'comision_tipo' => $comisionTipo,
                    'comision_monto' => $comisionValor,
                ]);
            }

            $this->metaWhatsAppService->sendCitaCreadaMessage($cita);
            $citasCreadas[] = $cita;
        }

        $primerFecha = $fechasAgenda[0] ?? $validated['fecha'];
        $especialistaAsignado = User::find($validated['especialista_id']);
        $mensajeExito = count($citasCreadas) > 1
            ? 'Serie de turnos creada correctamente: ' . count($citasCreadas) . ' sesiones agendadas.'
            : 'Turno agendado exitosamente.';

        if ($especialistaAsignado) {
            foreach ($citasCreadas as $citaCreada) {
                Notification::send($especialistaAsignado, new NuevaCitaAsignada($citaCreada, $paciente, $serviciosSeleccionados));

                if (!empty($validated['asistente_id'])) {
                    $asistente = User::find($validated['asistente_id']);
                    if ($asistente) {
                        Notification::send($asistente, new NuevaCitaAsignada($citaCreada, $paciente, $serviciosSeleccionados));
                    }
                }
            }

            if ($especialistaAsignado->id !== Auth::id()) {
                return redirect()->route('agenda.index', [
                    'fecha' => $primerFecha,
                    'especialista_id' => $validated['especialista_id'],
                ])->with('success', $mensajeExito)->with('notification', 'Se te asignó una nueva cita para ' . $paciente->nombre);
            }

            return redirect()->route('agenda.index', [
                'fecha' => $primerFecha,
                'especialista_id' => $validated['especialista_id'],
            ])->with('success', $mensajeExito);
        }

        return redirect()->route('agenda.index', [
            'fecha' => $primerFecha,
            'especialista_id' => $validated['especialista_id'],
        ])->with('success', $mensajeExito);
    }

    public function update(Request $request, Cita $cita)
    {
        $user = Auth::user();

        if (!$user || ($user->role !== 'admin' && $cita->user_id !== $user->id)) {
            abort(403, 'No tienes permisos para actualizar este turno.');
        }

        if ($request->hasAny(['paciente_id', 'especialista_id', 'servicios', 'fecha', 'hora_inicio', 'holgura_min'])) {
            abort_unless($user->role === 'admin', 403, 'Solo los administradores pueden modificar los datos de la cita.');

            $validated = $request->validate([
                'paciente_id' => 'required|exists:pacientes,id',
                'especialista_id' => 'required|exists:users,id',
                'asistente_id' => 'nullable|exists:users,id|different:especialista_id',
                'comision_asistente' => 'nullable|numeric|min:0|max:100',
                'servicios' => 'required|array|min:1',
                'servicios.*' => 'integer|exists:servicios,id',
                'precios_servicios' => 'nullable|array',
                'precios_servicios.*' => 'nullable|numeric|min:0',
                'servicio_especialistas' => 'nullable|array',
                'servicio_especialistas.*' => 'nullable|exists:users,id',
                'servicio_comisiones' => 'nullable|array',
                'servicio_comisiones.*' => 'nullable|numeric|min:0|max:100',
                'servicio_comision_tipos' => 'nullable|array',
                'servicio_comision_tipos.*' => 'nullable|in:porcentaje,monto',
                'servicio_comision_montos' => 'nullable|array',
                'servicio_comision_montos.*' => 'nullable|numeric|min:0',
                'fecha' => 'required|date',
                'hora_inicio' => 'required|date_format:H:i',
                'holgura_min' => 'required|integer|min:0',
            ]);

            $servicios = Servicio::whereIn('id', $validated['servicios'])->get();
            if (!empty($validated['asistente_id'])) {
                abort_unless(User::whereKey($validated['asistente_id'])->where('role', 'especialista')->exists(), 422, 'El asistente debe ser un especialista.');
            }

            $duracionTotal = $servicios->sum(fn ($servicio) => (int) ($servicio->duracion_min ?? 0));
            $conflicto = $this->buscarConflicto(
                $validated['paciente_id'],
                $validated['especialista_id'],
                $validated['fecha'],
                $validated['hora_inicio'],
                $validated['holgura_min'],
                $duracionTotal,
                $cita->id
            );

            if ($conflicto) {
                return redirect()->back()->withErrors(['disponibilidad' => 'No se puede modificar porque ' . $conflicto])->withInput();
            }

            $horaInicio = Carbon::parse($validated['fecha'] . ' ' . $validated['hora_inicio']);
            $subtotalServicios = $servicios->sum(function ($servicio) use ($request, $validated) {
                $especialistaServicioId = (int) ($request->input('servicio_especialistas.' . $servicio->id, $validated['especialista_id']) ?? $validated['especialista_id']);
                $precioManual = $request->input('precios_servicios.' . $servicio->id);
                $precio = $precioManual !== null && $precioManual !== ''
                    ? (float) $precioManual
                    : (float) $servicio->getPrecioParaEspecialista($especialistaServicioId);

                return $precio;
            });

            $tasas = $this->tasaCambioService->obtener();
            $comisionAsistente = !empty($validated['asistente_id']) ? (float) ($validated['comision_asistente'] ?? 3) : 0;
            $montoAsistente = !empty($validated['asistente_id']) ? round($subtotalServicios * ($comisionAsistente / 100), 2) : 0;
            $montoTotal = $subtotalServicios + $montoAsistente;
            $montoTotalBs = round($montoTotal * (float) $tasas->euro_bcv, 2);

            $cita->update([
                'paciente_id' => $validated['paciente_id'],
                'user_id' => $validated['especialista_id'],
                'asistente_id' => $validated['asistente_id'] ?? null,
                'comision_asistente_porcentaje' => !empty($validated['asistente_id']) ? $comisionAsistente : null,
                'fecha' => $validated['fecha'],
                'hora_inicio' => $horaInicio->format('H:i:s'),
                'hora_fin' => $horaInicio->copy()->addMinutes($duracionTotal + $validated['holgura_min'])->format('H:i:s'),
                'holgura_min' => $validated['holgura_min'],
                'monto_total' => $montoTotal,
                'monto_total_bs' => $montoTotalBs,
            ]);

            $cita->servicios()->sync($servicios->mapWithKeys(function ($servicio) use ($request, $validated) {
                $especialistaServicioId = (int) ($request->input('servicio_especialistas.' . $servicio->id, $validated['especialista_id']) ?? $validated['especialista_id']);
                $precioManual = $request->input('precios_servicios.' . $servicio->id);
                $precioMomento = $precioManual !== null && $precioManual !== ''
                    ? (float) $precioManual
                    : (float) $servicio->getPrecioParaEspecialista($especialistaServicioId);
                $comisionTipo = $request->input('servicio_comision_tipos.' . $servicio->id, 'porcentaje');
                $comisionValor = $comisionTipo === 'monto'
                    ? (float) ($request->input('servicio_comision_montos.' . $servicio->id, 0) ?? 0)
                    : round($precioMomento * ((float) ($request->input('servicio_comisiones.' . $servicio->id, 0) ?? 0) / 100), 2);
                $comisionPorcentaje = $comisionTipo === 'monto' && $precioMomento > 0
                    ? round(($comisionValor / $precioMomento) * 100, 2)
                    : (float) ($request->input('servicio_comisiones.' . $servicio->id, 0) ?? 0);

                return [
                    $servicio->id => [
                        'precio_momento' => $precioMomento,
                        'monto_bs_momento' => round($precioMomento * (float) $this->tasaCambioService->obtener()->euro_bcv, 2),
                        'duracion_momento' => (int) ($servicio->duracion_min ?? 0),
                        'especialista_id' => $especialistaServicioId,
                        'comision_momento' => $comisionPorcentaje,
                        'comision_tipo' => $comisionTipo,
                        'comision_monto' => $comisionValor,
                    ],
                ];
            })->all());

            return redirect()->route('agenda.index', [
                'fecha' => $validated['fecha'],
                'especialista_id' => $validated['especialista_id'],
            ])->with('success', 'Cita modificada correctamente.');
        }

        $validated = $request->validate([
            'estado' => 'required|string|in:Confirmado,En Proceso,Completado,Cancelado',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        $cita->estado = $this->mapEstadoParaBaseDatos($validated['estado']);
        $cita->observaciones = trim((string) ($validated['observaciones'] ?? $cita->observaciones ?? '')) ?: $cita->observaciones;
        $cita->save();

        return redirect()->route('agenda.index', [
            'fecha' => $cita->fecha ? Carbon::parse($cita->fecha)->format('Y-m-d') : date('Y-m-d'),
            'especialista_id' => $cita->user_id,
        ])->with('success', 'Estado del turno actualizado correctamente.');
    }

    public function destroy(Cita $cita)
    {
        $user = Auth::user();

        if (!$user || ($user->role !== 'admin' && $cita->user_id !== $user->id)) {
            abort(403, 'No tienes permisos para eliminar este turno.');
        }

        $fecha = $cita->fecha ? Carbon::parse($cita->fecha)->format('Y-m-d') : date('Y-m-d');
        $especialistaId = $cita->user_id;

        $cita->servicios()->detach();
        $cita->delete();

        return redirect()->route('agenda.index', [
            'fecha' => $fecha,
            'especialista_id' => $especialistaId,
        ])->with('success', 'Cita eliminada correctamente.');
    }

    private function mapEstadoParaBaseDatos(string $estado): string
    {
        switch ($estado) {
            case 'Confirmado':
                return 'confirmado';
            case 'En Proceso':
                return 'en_proceso';
            case 'Completado':
                return 'completado';
            case 'Cancelado':
                return 'cancelado';
            default:
                return 'confirmado';
        }
    }

    private function generarFechasRecurrencia(string $fechaInicio, string $tipo, int $cantidadSesiones, array $diasSemana): array
    {
        if ($tipo === 'ninguna' || $cantidadSesiones <= 1) {
            return [Carbon::parse($fechaInicio)->format('Y-m-d')];
        }

        $inicio = Carbon::parse($fechaInicio);
        $fechas = [];

        if ($tipo === 'mensual') {
            $fechaActual = $inicio->copy();
            while (count($fechas) < $cantidadSesiones) {
                $fechas[] = $fechaActual->format('Y-m-d');
                $fechaActual = $fechaActual->copy()->addMonth();
            }

            return $fechas;
        }

        if ($tipo === 'diario') {
            $fechaActual = $inicio->copy();
            while (count($fechas) < $cantidadSesiones) {
                $fechas[] = $fechaActual->format('Y-m-d');
                $fechaActual->addDay();
            }

            return $fechas;
        }

        $diasSeleccionados = $diasSemana;
        if (empty($diasSeleccionados)) {
            $diasSeleccionados = [$inicio->dayOfWeek];
        }

        $diasSeleccionados = array_values(array_unique($diasSeleccionados));
        sort($diasSeleccionados);

        $semanaActual = $inicio->copy()->startOfWeek(Carbon::MONDAY);
        $intervalo = $tipo === 'quincenal' ? 2 : 1;

        while (count($fechas) < $cantidadSesiones) {
            foreach ($diasSeleccionados as $diaSemana) {
                if (count($fechas) >= $cantidadSesiones) {
                    break;
                }

                $fechaPosible = $semanaActual->copy()->addDays($diaSemana);
                if ($fechaPosible->lt($inicio)) {
                    continue;
                }

                $fechas[] = $fechaPosible->format('Y-m-d');
            }

            $semanaActual->addWeeks($intervalo);
        }

        return array_slice($fechas, 0, $cantidadSesiones);
    }

    private function buscarConflicto($pacienteId, $especialistaId, $fecha, $horaInicio, $holguraMin, $duracionTotal, ?int $excluirCitaId = null)
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
            if ($excluirCitaId && $cita->id === $excluirCitaId) {
                continue;
            }

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