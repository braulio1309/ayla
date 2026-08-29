<?php

namespace App\Services;

use App\Models\AylaAdicional;
use App\Models\Cita;
use App\Models\PagoSemanalEspecialista;
use App\Models\Servicio;
use App\Models\User;
use Carbon\Carbon;

class ReporteService
{
    private const FILTRO_AYLA_ADICIONALES = 'ayla_adicionales';

    public function getReporteData(?string $periodo = null, int|string|null $especialistaId = null, ?int $servicioId = null): array
    {
        $periodo = $periodo ?: 'agosto_2026';
        $soloAylaAdicionales = $especialistaId === self::FILTRO_AYLA_ADICIONALES;

        $citas = Cita::with([
            'paciente' => function ($query) {
                $query->select('id', 'nombre');
            },
            'especialista' => function ($query) {
                $query->select('id', 'name', 'role', 'comision');
            },
            'asistente' => function ($query) {
                $query->select('id', 'name');
            },
            'servicios' => function ($query) {
                $query->select('servicios.id', 'servicios.nombre');
            },
        ]);

        $rango = $this->resolverRangoPeriodo($periodo);
        if ($rango) {
            $citas->whereBetween('fecha', [$rango['inicio'], $rango['fin']]);
        }

        if ($especialistaId && !$soloAylaAdicionales) {
            $citas->where(function ($query) use ($especialistaId) {
                $query->where('user_id', $especialistaId)
                    ->orWhere('asistente_id', $especialistaId)
                    ->orWhereHas('servicios', function ($serviciosQuery) use ($especialistaId) {
                        $serviciosQuery->where('cita_servicio.especialista_id', $especialistaId);
                    });
            });
        }

        if ($servicioId) {
            $citas->whereHas('servicios', function ($query) use ($servicioId) {
                $query->where('servicios.id', $servicioId);
            });
        }

        if ($soloAylaAdicionales) {
            $citas->whereRaw('1 = 0');
        }

        $citas = $citas->get();
        $adicionales = collect();
        if ((!$especialistaId || $soloAylaAdicionales) && !$servicioId) {
            $adicionalesQuery = AylaAdicional::query();
            if ($rango) {
                $adicionalesQuery->whereBetween('fecha', [$rango['inicio'], $rango['fin']]);
            }
            $adicionales = $adicionalesQuery->orderByDesc('fecha')->get();
        }

        $totalAdicionales = (float) $adicionales->sum('monto');
        $totalAdicionalesBs = (float) $adicionales->sum('monto_bs');
        $totalGeneral = (float) $citas->sum('monto_total') + $totalAdicionales;
        $totalGeneralBs = (float) $citas->sum('monto_total_bs') + $totalAdicionalesBs;
        $totalesEspecialista = null;

        if ($especialistaId && !$soloAylaAdicionales) {
            $totalesEspecialista = $this->calcularTotalesEspecialista($citas, (int) $especialistaId);
            $totalGeneral = $totalesEspecialista['ingresos_brutos'];
            $totalGeneralBs = $totalesEspecialista['ingresos_brutos_bs'];
        }

        $auditoriaMap = [];
        foreach ($citas as $cita) {
            foreach ($cita->servicios as $servicio) {
                $espId = (int) ($servicio->pivot->especialista_id ?: $cita->user_id);
                $esp = $espId === (int) $cita->user_id ? $cita->especialista : User::find($espId);
                $espNombre = $esp ? $esp->name : 'Sin especialista';
                $espRole = $esp ? $esp->role : 'especialista';

                if (!isset($auditoriaMap[$espId])) {
                    $auditoriaMap[$espId] = [
                        'especialista' => $espNombre,
                        'user_id' => $espId,
                        'categoria' => $espRole === 'especialista' ? 'Especialista' : 'Administrador',
                        'citas_completadas' => 0,
                        'citas_ids' => [],
                        'ingreso_generado' => 0,
                        'ingreso_generado_bs' => 0,
                        'comision_especialista' => 0,
                        'comision_especialista_bs' => 0,
                        'comision_asistentes' => 0,
                        'comision_asistentes_bs' => 0,
                    ];
                }

                if (!in_array($cita->id, $auditoriaMap[$espId]['citas_ids'])) {
                    $auditoriaMap[$espId]['citas_ids'][] = $cita->id;
                    $auditoriaMap[$espId]['citas_completadas']++;
                }

                $precio = (float) ($servicio->pivot->precio_momento ?? 0);
                $precioBs = (float) ($servicio->pivot->monto_bs_momento ?? 0);
                $comisionPct = (float) ($servicio->pivot->comision_momento ?? ($esp->comision ?? 0));

                $auditoriaMap[$espId]['ingreso_generado'] += $precio;
                $auditoriaMap[$espId]['ingreso_generado_bs'] += $precioBs;
                $auditoriaMap[$espId]['comision_especialista'] += round($precio * ($comisionPct / 100), 2);
                $auditoriaMap[$espId]['comision_especialista_bs'] += round($precioBs * ($comisionPct / 100), 2);
            }

            if ($cita->asistente_id) {
                $asistenteId = (int) $cita->asistente_id;
                $asistente = $cita->asistente ?: User::find($asistenteId);
                if (!isset($auditoriaMap[$asistenteId])) {
                    $auditoriaMap[$asistenteId] = [
                        'especialista' => $asistente ? $asistente->name : 'Asistente',
                        'user_id' => $asistenteId,
                        'categoria' => 'Asistente',
                        'citas_completadas' => 0,
                        'citas_ids' => [],
                        'ingreso_generado' => 0,
                        'ingreso_generado_bs' => 0,
                        'comision_especialista' => 0,
                        'comision_especialista_bs' => 0,
                        'comision_asistentes' => 0,
                        'comision_asistentes_bs' => 0,
                    ];
                }
                $subtotalCita = (float) $cita->servicios->sum('pivot.precio_momento');
                if ($subtotalCita <= 0) $subtotalCita = (float) $cita->monto_total;

                $subtotalCitaBs = (float) $cita->servicios->sum('pivot.monto_bs_momento');
                if ($subtotalCitaBs <= 0) $subtotalCitaBs = (float) $cita->monto_total_bs;

                $pct = (float) ($cita->comision_asistente_porcentaje ?? 0);
                $auditoriaMap[$asistenteId]['comision_asistentes'] += round($subtotalCita * ($pct / 100), 2);
                $auditoriaMap[$asistenteId]['comision_asistentes_bs'] += round($subtotalCitaBs * ($pct / 100), 2);
            }
        }

        $auditoria = array_values(array_map(function ($item) use ($totalGeneral, $totalGeneralBs) {
            $totalGenerado = $item['ingreso_generado'];
            $totalGeneradoBs = $item['ingreso_generado_bs'];
            $item['ganancia_negocio'] = round($totalGenerado - $item['comision_especialista'] - $item['comision_asistentes'], 2);
            $item['ganancia_negocio_bs'] = round($totalGeneradoBs - $item['comision_especialista_bs'] - $item['comision_asistentes_bs'], 2);
            $item['aporte_porcentaje'] = $totalGeneral > 0 ? round(($totalGenerado / $totalGeneral) * 100, 1) . '%' : '0%';
            unset($item['citas_ids']);
            return $item;
        }, $auditoriaMap));

        if ($totalAdicionales > 0 || $totalAdicionalesBs > 0) {
            $auditoria[] = [
                'especialista' => 'Ayla Adicionales',
                'categoria' => 'Ingreso adicional del negocio',
                'citas_completadas' => $adicionales->count(),
                'ingreso_generado' => round($totalAdicionales, 2),
                'ingreso_generado_bs' => round($totalAdicionalesBs, 2),
                'comision_especialista' => 0,
                'comision_especialista_bs' => 0,
                'comision_asistentes' => 0,
                'comision_asistentes_bs' => 0,
                'ganancia_negocio' => round($totalAdicionales, 2),
                'ganancia_negocio_bs' => round($totalAdicionalesBs, 2),
                'aporte_porcentaje' => $totalGeneral > 0 ? round(($totalAdicionales / $totalGeneral) * 100, 1) . '%' : '0%',
            ];
        }

        if ($periodo === 'semanal') {
            $pagosPorEspecialista = PagoSemanalEspecialista::query()
                ->whereDate('semana_inicio', $rango['inicio'])
                ->whereDate('semana_fin', $rango['fin'])
                ->get()
                ->keyBy('especialista_id');

            $auditoria = array_map(function ($item) use ($pagosPorEspecialista) {
                if (!isset($item['user_id'])) {
                    $item['semana_pagada'] = false;
                    return $item;
                }

                $pago = $pagosPorEspecialista->get($item['user_id']);
                $item['semana_pagada'] = (bool) $pago;
                $item['semana_pagada_at'] = $pago?->pagado_at?->format('d/m/Y H:i');
                return $item;
            }, $auditoria);
        }

        $totalComisionEspecialistas = round(array_sum(array_column($auditoria, 'comision_especialista')), 2);
        $totalComisionEspecialistasBs = round(array_sum(array_column($auditoria, 'comision_especialista_bs')), 2);
        $totalComisionAsistentes = round(array_sum(array_column($auditoria, 'comision_asistentes')), 2);
        $totalComisionAsistentesBs = round(array_sum(array_column($auditoria, 'comision_asistentes_bs')), 2);
        $totalNegocio = round(array_sum(array_column($auditoria, 'ganancia_negocio')), 2);
        $totalNegocioBs = round(array_sum(array_column($auditoria, 'ganancia_negocio_bs')), 2);

        if ($totalesEspecialista) {
            $totalComisionEspecialistas = $totalesEspecialista['comision_especialista'];
            $totalComisionEspecialistasBs = $totalesEspecialista['comision_especialista_bs'];
            $totalComisionAsistentes = $totalesEspecialista['comision_asistentes'];
            $totalComisionAsistentesBs = $totalesEspecialista['comision_asistentes_bs'];
            $totalNegocio = round($totalGeneral - $totalComisionEspecialistas - $totalComisionAsistentes, 2);
            $totalNegocioBs = round($totalGeneralBs - $totalComisionEspecialistasBs - $totalComisionAsistentesBs, 2);
        }

        $agendas = $citas->sortByDesc('fecha')->values()->map(function ($cita) use ($especialistaId, $soloAylaAdicionales) {
            $serviciosDetalle = $cita->servicios->map(function ($servicio) use ($cita) {
                $especialistaId = (int) ($servicio->pivot->especialista_id ?: $cita->user_id);
                $especialista = $especialistaId === (int) $cita->user_id
                    ? $cita->especialista
                    : User::find($especialistaId);
                $precio = (float) ($servicio->pivot->precio_momento ?? 0);
                $precioBs = (float) ($servicio->pivot->monto_bs_momento ?? 0);
                $comision = (float) ($servicio->pivot->comision_momento ?? ($especialista?->comision ?? 0));
                $comisionTipo = $servicio->pivot->comision_tipo ?? 'porcentaje';
                $comisionMonto = (float) ($servicio->pivot->comision_monto ?? ($precio * ($comision / 100)));

                return [
                    'id' => $servicio->id,
                    'nombre' => $servicio->nombre,
                    'especialista' => $especialista?->name ?? 'Sin especialista',
                    'especialista_id' => $especialistaId,
                    'precio' => $precio,
                    'precio_bs' => $precioBs,
                    'comision_porcentaje' => $comision,
                    'comision_tipo' => $comisionTipo,
                    'comision_monto' => $comisionMonto,
                    'comision' => $comisionMonto,
                    'comision_bs' => $precio > 0 ? round($precioBs * ($comisionMonto / $precio), 2) : 0,
                ];
            })->values()->all();

            $gananciaFiltrada = $especialistaId && !$soloAylaAdicionales
                ? collect($serviciosDetalle)->where('especialista_id', (int) $especialistaId)->sum('comision')
                : array_sum(array_column($serviciosDetalle, 'comision'));
            $gananciaFiltradaBs = $especialistaId && !$soloAylaAdicionales
                ? collect($serviciosDetalle)->where('especialista_id', (int) $especialistaId)->sum('comision_bs')
                : array_sum(array_column($serviciosDetalle, 'comision_bs'));

            if ($especialistaId && !$soloAylaAdicionales && (int) $cita->asistente_id === (int) $especialistaId) {
                $subtotal = (float) $cita->servicios->sum('pivot.precio_momento');
                $subtotalBs = (float) $cita->servicios->sum('pivot.monto_bs_momento');
                $porcentajeAsistente = (float) ($cita->comision_asistente_porcentaje ?? 0);
                $gananciaFiltrada += round($subtotal * ($porcentajeAsistente / 100), 2);
                $gananciaFiltradaBs += round($subtotalBs * ($porcentajeAsistente / 100), 2);
            }

            return [
                'id' => $cita->id,
                'fecha' => $cita->fecha?->format('d/m/Y'),
                'hora' => $cita->hora_inicio ? substr((string) $cita->hora_inicio, 0, 5) : 'N/A',
                'paciente' => $cita->paciente?->nombre ?? 'Sin paciente',
                'servicio' => $cita->servicios->pluck('nombre')->join(', ') ?: 'Sin servicio',
                'servicios_detalle' => $serviciosDetalle,
                'asistente' => $cita->asistente?->name,
                'comision_asistente_porcentaje' => (float) ($cita->comision_asistente_porcentaje ?? 0),
                'estado' => $cita->estado,
                'monto' => (float) ($cita->monto_total ?? 0),
                'monto_bs' => (float) ($cita->monto_total_bs ?? 0),
                'total_ganado_filtrado' => round($gananciaFiltrada, 2),
                'total_ganado_filtrado_bs' => round($gananciaFiltradaBs, 2),
            ];
        })->all();

        return [
            'filters' => [
                'periodo' => $periodo,
                'especialista_id' => $especialistaId,
                'servicio_id' => $servicioId,
                'es_semana_actual' => $periodo === 'semanal',
            ],
            'kpis' => [
                'ingresos_brutos' => $totalGeneral,
                'ingresos_brutos_bs' => $totalGeneralBs,
                'total_comision_especialistas' => $totalComisionEspecialistas,
                'total_comision_especialistas_bs' => $totalComisionEspecialistasBs,
                'total_comision_asistentes' => $totalComisionAsistentes,
                'total_comision_asistentes_bs' => $totalComisionAsistentesBs,
                'total_negocio' => $totalNegocio,
                'total_negocio_bs' => $totalNegocioBs,
                'total_citas' => $citas->count(),
                'promedio_cita' => $citas->count() > 0 ? round($citas->sum('monto_total') / $citas->count(), 2) : 0,
                'top_especialista' => $auditoria[0]['especialista'] ?? 'Sin datos',
                'top_especialista_monto' => $auditoria[0]['ingreso_generado'] ?? 0,
                'top_especialista_porcentaje' => $auditoria[0]['aporte_porcentaje'] ?? '0%',
            ],
            'auditoria_especialistas' => $auditoria,
            'agendas' => $agendas,
            'ayla_adicionales' => $adicionales->map(fn ($adicional) => [
                'fecha' => $adicional->fecha?->format('d/m/Y'),
                'descripcion' => $adicional->descripcion,
                'monto' => (float) $adicional->monto,
                'monto_bs' => (float) $adicional->monto_bs,
            ])->all(),
            'especialistas_lista' => User::where('role', 'especialista')->select('id', 'name')->get()->map(fn ($u) => [
                'id' => $u->id,
                'nombre' => $u->name,
            ])->all(),
            'servicios_lista' => Servicio::select('id', 'nombre')->get()->map(fn ($s) => [
                'id' => $s->id,
                'nombre' => $s->nombre,
            ])->all(),
        ];
    }

    private function calcularTotalesEspecialista($citas, int $especialistaId): array
    {
        $totales = [
            'ingresos_brutos' => 0,
            'ingresos_brutos_bs' => 0,
            'comision_especialista' => 0,
            'comision_especialista_bs' => 0,
            'comision_asistentes' => 0,
            'comision_asistentes_bs' => 0,
        ];

        foreach ($citas as $cita) {
            foreach ($cita->servicios as $servicio) {
                $especialistaServicioId = (int) ($servicio->pivot->especialista_id ?: $cita->user_id);
                if ($especialistaServicioId !== $especialistaId) {
                    continue;
                }

                $precio = (float) ($servicio->pivot->precio_momento ?? 0);
                $precioBs = (float) ($servicio->pivot->monto_bs_momento ?? 0);
                $comision = (float) ($servicio->pivot->comision_monto ?? 0);
                $comisionBs = $precio > 0 ? round($precioBs * ($comision / $precio), 2) : 0;

                $totales['ingresos_brutos'] += $precio;
                $totales['ingresos_brutos_bs'] += $precioBs;
                $totales['comision_especialista'] += $comision;
                $totales['comision_especialista_bs'] += $comisionBs;
            }

            if ((int) $cita->asistente_id === $especialistaId) {
                $subtotal = (float) $cita->servicios->sum('pivot.precio_momento');
                if ($subtotal <= 0) $subtotal = (float) $cita->monto_total;
                $subtotalBs = (float) $cita->servicios->sum('pivot.monto_bs_momento');
                if ($subtotalBs <= 0) $subtotalBs = (float) $cita->monto_total_bs;
                $porcentaje = (float) ($cita->comision_asistente_porcentaje ?? 0);

                $totales['ingresos_brutos'] += $subtotal;
                $totales['ingresos_brutos_bs'] += $subtotalBs;
                $totales['comision_asistentes'] += round($subtotal * ($porcentaje / 100), 2);
                $totales['comision_asistentes_bs'] += round($subtotalBs * ($porcentaje / 100), 2);
            }
        }

        return array_map(fn ($monto) => round($monto, 2), $totales);
    }

    private function resolverRangoPeriodo(string $periodo): ?array
    {
        $periodoNormalizado = strtolower(trim($periodo));

        if ($periodoNormalizado === 'hoy') {
            $hoy = Carbon::today();

            return [
                'inicio' => $hoy->format('Y-m-d'),
                'fin' => $hoy->format('Y-m-d'),
            ];
        }

        if ($periodoNormalizado === 'semanal') {
            $inicio = Carbon::now()->startOfWeek(Carbon::MONDAY);
            $fin = $inicio->copy()->addDays(5);

            return [
                'inicio' => $inicio->format('Y-m-d'),
                'fin' => $fin->format('Y-m-d'),
            ];
        }

        if ($periodoNormalizado === 'anual') {
            $inicio = Carbon::now()->startOfYear();
            $fin = Carbon::now()->endOfYear();

            return [
                'inicio' => $inicio->format('Y-m-d'),
                'fin' => $fin->format('Y-m-d'),
            ];
        }

        if (preg_match('/^([a-z]+)_(\d{4})$/', $periodoNormalizado, $coincidencias)) {
            $mesMap = [
                'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4, 'mayo' => 5, 'junio' => 6,
                'julio' => 7, 'agosto' => 8, 'septiembre' => 9, 'setiembre' => 9, 'octubre' => 10,
                'noviembre' => 11, 'diciembre' => 12,
            ];

            $mes = $mesMap[$coincidencias[1]] ?? null;
            $anio = (int) $coincidencias[2];

            if ($mes) {
                $inicio = Carbon::create($anio, $mes, 1, 0, 0, 0);
                $fin = $inicio->copy()->endOfMonth();

                return [
                    'inicio' => $inicio->format('Y-m-d'),
                    'fin' => $fin->format('Y-m-d'),
                ];
            }
        }

        return null;
    }
}
