<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AgendaService
{
    public function __construct(private TasaCambioService $tasaCambioService)
    {
    }

    public function getAgendaData(?string $fecha = null, ?int $especialistaId = null, ?string $estado = null): array
    {
        $fecha = $fecha ?: now()->format('Y-m-d');
        $fechaCarbon = Carbon::parse($fecha);

        $query = Cita::with(['paciente', 'servicios', 'especialista', 'asistente'])
            ->whereDate('fecha', $fecha);

        $usuarioActual = Auth::user();
        if ($usuarioActual && $usuarioActual->role === 'especialista') {
            $query->where('user_id', $usuarioActual->id);
        } elseif ($especialistaId) {
            $query->where('user_id', $especialistaId);
        }

        if ($estado) {
            $query->where('estado', $this->mapEstadoParaBaseDatos($estado));
        }

        $turnos = $query->orderBy('hora_inicio')->get()->map(function ($cita) {
            $duracion = $cita->servicios->sum(function ($servicio) {
                return (int) ($servicio->pivot->duracion_momento ?? 0);
            });

            $estadoTexto = $this->normalizarEstado($cita->estado);

            return [
                'id' => $cita->id,
                'hora_inicio' => $cita->hora_inicio ? Carbon::parse($cita->hora_inicio)->format('h:i A') : '',
                'hora_fin' => $cita->hora_fin ? Carbon::parse($cita->hora_fin)->format('h:i A') : '',
                'duracion_min' => $duracion > 0 ? $duracion : (int) ($cita->holgura_min ?? 0),
                'paciente' => ($cita->paciente ? $cita->paciente->nombre : null) ?? 'Sin paciente',
                'paciente_id' => $cita->paciente_id,
                'servicio_ids' => $cita->servicios->pluck('id')->values()->all(),
                'servicio' => $cita->servicios->pluck('nombre')->join(', ') ?: 'Sin servicio',
                'especialista' => ($cita->especialista ? $cita->especialista->name : null) ?? 'Sin especialista',
                'especialista_id' => $cita->user_id,
                'asistente' => $cita->asistente?->name,
                'asistente_id' => $cita->asistente_id,
                'comision_asistente_porcentaje' => (float) ($cita->comision_asistente_porcentaje ?? 0),
                'monto' => (float) ($cita->monto_total ?? 0),
                'monto_bs' => (float) ($cita->monto_total_bs ?? 0),
                'tasa_dolar_bcv' => (float) ($cita->tasa_dolar_bcv ?? 0),
                'tasa_euro_bcv' => (float) ($cita->tasa_euro_bcv ?? 0),
                'cabina' => $cita->cabina ?? 'Sin cabina',
                'estado' => $estadoTexto,
                'observaciones' => $cita->observaciones ?? '',
            ];
        })->values()->all();

        $calendario = $this->buildCalendarioData($fechaCarbon, $usuarioActual, $especialistaId, $estado);
        $serviciosLista = Servicio::with(['especialistas' => function ($query) use ($especialistaId, $usuarioActual) {
            if ($especialistaId) {
                $query->where('users.id', $especialistaId);
            } elseif ($usuarioActual && $usuarioActual->role === 'especialista') {
                $query->where('users.id', $usuarioActual->id);
            }
        }])->get()->map(function ($s) use ($especialistaId, $usuarioActual) {
            $tasaDolar = (float) $this->tasaCambioService->obtener()->dolar_bcv;
            $especialistaAsignado = $especialistaId
                ? $s->especialistas->first()
                : ($usuarioActual && $usuarioActual->role === 'especialista'
                    ? $s->especialistas->first()
                    : null);

            $precio = $especialistaAsignado
                ? (float) ($especialistaAsignado->pivot->precio_especialista ?? $s->precio_base ?? 0)
                : (float) ($s->precio_base ?? 0);

            return [
                'id' => $s->id,
                'nombre' => $s->nombre,
                'precio' => $precio,
                'precio_bs' => round($precio * $tasaDolar, 2),
                'duracion' => (int) ($s->duracion_min ?? 0),
                'es_recurrente' => (bool) ($s->es_recurrente ?? true),
                'especialistas' => $s->especialistas->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'precio_especialista' => (float) ($u->pivot->precio_especialista ?? $s->precio_base ?? 0),
                ])->values()->all(),
            ];
        })->values()->all();

        return [
            'filters' => [
                'fecha' => $fecha,
                'especialista_id' => $especialistaId,
                'estado' => $estado,
            ],
            'turnos' => $turnos,
            'calendario' => $calendario,
            'pacientes_lista' => Paciente::select('id', 'nombre', 'cedula')->get()->map(fn ($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'cedula' => $p->cedula,
            ])->all(),
            'servicios_lista' => $serviciosLista,
            'especialistas_lista' => User::where('role', 'especialista')->select('id', 'name')->get()->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
            ])->all(),
        ];
    }

    private function buildCalendarioData(Carbon $fecha, ?User $usuarioActual = null, ?int $especialistaId = null, ?string $estado = null): array
    {
        $query = Cita::with(['paciente', 'servicios', 'especialista'])
            ->whereYear('fecha', $fecha->year)
            ->whereMonth('fecha', $fecha->month);

        if ($usuarioActual && $usuarioActual->role === 'especialista') {
            $query->where('user_id', $usuarioActual->id);
        } elseif ($especialistaId) {
            $query->where('user_id', $especialistaId);
        }

        if ($estado) {
            $query->where('estado', $this->mapEstadoParaBaseDatos($estado));
        }

        $conteoPorDia = [];
        foreach ($query->get() as $cita) {
            $dia = $cita->fecha ? Carbon::parse($cita->fecha)->format('Y-m-d') : null;
            if ($dia) {
                $conteoPorDia[$dia] = ($conteoPorDia[$dia] ?? 0) + 1;
            }
        }

        return $conteoPorDia;
    }

    private function normalizarEstado($estado): string
    {
        switch ($estado) {
            case 'confirmado':
                return 'Confirmado';
            case 'en_proceso':
                return 'En Proceso';
            case 'completado':
                return 'Completado';
            case 'cancelado':
                return 'Cancelado';
            default:
                return 'Confirmado';
        }
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
}
