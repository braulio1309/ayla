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

            $subtotalServicios = (float) $cita->servicios->sum('pivot.precio_momento');
            if ($subtotalServicios <= 0) $subtotalServicios = (float) $cita->monto_total;

            $subtotalServiciosBs = (float) $cita->servicios->sum('pivot.monto_bs_momento');
            if ($subtotalServiciosBs <= 0) $subtotalServiciosBs = (float) $cita->monto_total_bs;

            $montoAsistente = $cita->asistente_id
                ? round($subtotalServicios * (((float) ($cita->comision_asistente_porcentaje ?? 0)) / 100), 2)
                : 0;

            $montoAsistenteBs = $cita->asistente_id
                ? round($subtotalServiciosBs * (((float) ($cita->comision_asistente_porcentaje ?? 0)) / 100), 2)
                : 0;

            $especialistasCita = User::whereIn('id', $cita->servicios->pluck('pivot.especialista_id')->merge($cita->servicios->pluck('pivot.lavado_especialista_id'))->filter()->unique())->get()->keyBy('id');

            $serviciosDetalle = $cita->servicios->map(function ($servicio) use ($cita, $especialistasCita) {
                $espId = (int) ($servicio->pivot->especialista_id ?: $cita->user_id);
                $espNombre = $espId === (int) $cita->user_id ? ($cita->especialista?->name ?? 'Especialista') : ($especialistasCita->get($espId)?->name ?? 'Especialista');
                $precio = (float) ($servicio->pivot->precio_momento ?? 0);
                $precioBs = (float) ($servicio->pivot->monto_bs_momento ?? 0);
                $comisionPct = (float) ($servicio->pivot->comision_momento ?? 0);
                $comisionTipo = $servicio->pivot->comision_tipo ?? 'porcentaje';
                $comisionMonto = (float) ($servicio->pivot->comision_monto ?? ($precio * ($comisionPct / 100)));

                return [
                    'id' => $servicio->id,
                    'nombre' => $servicio->nombre,
                    'precio' => $precio,
                    'precio_bs' => $precioBs,
                    'especialista_id' => $espId,
                    'especialista_nombre' => $espNombre,
                    'comision_porcentaje' => $comisionPct,
                    'comision_tipo' => $comisionTipo,
                    'comision_monto' => $comisionMonto,
                    'requiere_lavado' => (bool) $servicio->pivot->requiere_lavado,
                    'lavado_especialista_id' => $servicio->pivot->lavado_especialista_id,
                    'lavado_especialista_nombre' => $especialistasCita->get($servicio->pivot->lavado_especialista_id)?->name,
                    'lavado_monto' => (float) ($servicio->pivot->lavado_monto ?? 0),
                    'ganancia' => $comisionMonto,
                    'ganancia_bs' => $precio > 0 ? round($precioBs * ($comisionMonto / $precio), 2) : 0,
                ];
            })->values()->all();

            return [
                'id' => $cita->id,
                'fecha' => $cita->fecha ? Carbon::parse($cita->fecha)->format('Y-m-d') : null,
                'hora_inicio' => $cita->hora_inicio ? Carbon::parse($cita->hora_inicio)->format('h:i A') : '',
                'hora_fin' => $cita->hora_fin ? Carbon::parse($cita->hora_fin)->format('h:i A') : '',
                'duracion_min' => $duracion > 0 ? $duracion : (int) ($cita->holgura_min ?? 0),
                'paciente' => ($cita->paciente ? $cita->paciente->nombre : null) ?? 'Sin paciente',
                'paciente_id' => $cita->paciente_id,
                'servicio_ids' => $cita->servicios->pluck('id')->values()->all(),
                'servicio' => $cita->servicios->pluck('nombre')->join(', ') ?: 'Sin servicio',
                'servicios_detalle' => $serviciosDetalle,
                'especialista' => ($cita->especialista ? $cita->especialista->name : null) ?? 'Sin especialista',
                'especialista_id' => $cita->user_id,
                'asistente' => $cita->asistente?->name,
                'asistente_id' => $cita->asistente_id,
                'comision_asistente_porcentaje' => (float) ($cita->comision_asistente_porcentaje ?? 0),
                'holgura_min' => (int) ($cita->holgura_min ?? 15),
                'monto_asistente' => $montoAsistente,
                'monto_asistente_bs' => $montoAsistenteBs,
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
        $tasaEuro = (float) $this->tasaCambioService->obtener()->euro_bcv;

        $serviciosLista = Servicio::with(['especialistas' => function ($query) use ($especialistaId, $usuarioActual) {
            if ($especialistaId) {
                $query->where('users.id', $especialistaId);
            } elseif ($usuarioActual && $usuarioActual->role === 'especialista') {
                $query->where('users.id', $usuarioActual->id);
            }
        }])->get()->map(function ($s) use ($especialistaId, $usuarioActual, $tasaEuro) {
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
                'precio_bs' => round($precio * $tasaEuro, 2),
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
            'especialistas_lista' => User::where('role', 'especialista')->select('id', 'name', 'comision')->get()->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'comision' => (float) ($u->comision ?? 0),
            ])->all(),
        ];
    }

    private function buildCalendarioData(Carbon $fecha, ?User $usuarioActual = null, ?int $especialistaId = null, ?string $estado = null): array
    {
        $query = Cita::query()
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

        return $query->selectRaw('DATE(fecha) as fecha_str, COUNT(*) as total')
            ->groupBy('fecha_str')
            ->pluck('total', 'fecha_str')
            ->toArray();
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
