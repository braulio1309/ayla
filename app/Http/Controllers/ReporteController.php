<?php

namespace App\Http\Controllers;

use App\Models\AylaAdicional;
use App\Models\PagoSemanalEspecialista;
use App\Services\ReporteService;
use App\Services\TasaCambioService;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReporteController extends Controller
{
    protected $reporteService;

    public function __construct(ReporteService $reporteService)
    {
        $this->reporteService = $reporteService;
    }

    public function index(Request $request)
    {
        abort_unless(Auth::user()?->role === 'admin', 403, 'No tienes permisos para ver finanzas.');

        $periodo = $request->input('periodo', 'agosto_2026');
        $especialistaId = $request->input('especialista_id', '');
        $servicioId = $request->input('servicio_id', '');

        $data = $this->reporteService->getReporteData($periodo, $especialistaId ?: null, $servicioId ? (int) $servicioId : null);

        return Inertia::render('Reportes', [
            'filters' => $data['filters'],
            'kpis' => $data['kpis'],
            'auditoria_especialistas' => $data['auditoria_especialistas'],
            'agendas' => $data['agendas'],
            'ayla_adicionales' => $data['ayla_adicionales'],
            'especialistas_lista' => $data['especialistas_lista'],
            'servicios_lista' => $data['servicios_lista'],
        ]);
    }

    public function exportar(Request $request)
    {
        abort_unless(Auth::user()?->role === 'admin', 403, 'No tienes permisos para exportar finanzas.');

        $especialistaId = $request->input('especialista_id') ?: null;
        $servicioId = $request->integer('servicio_id') ?: null;
        $data = $this->reporteService->getReporteData(
            $request->input('periodo', 'agosto_2026'),
            $especialistaId,
            $servicioId
        );

        return response()->streamDownload(function () use ($data) {
            echo "\xEF\xBB\xBF";
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Fecha', 'Hora', 'Paciente', 'Servicio', 'Asistente', 'Estado', 'Monto USD', 'Monto Bs'], ';');

            foreach ($data['agendas'] as $agenda) {
                fputcsv($handle, [
                    $agenda['fecha'],
                    $agenda['hora'],
                    $agenda['paciente'],
                    $agenda['servicio'],
                    $agenda['asistente'] ? $agenda['asistente'] . ' (3%)' : 'Sin asistente',
                    $agenda['estado'],
                    number_format($agenda['monto'], 2, ',', ''),
                    number_format($agenda['monto_bs'], 2, ',', ''),
                ], ';');
            }

            foreach ($data['ayla_adicionales'] as $adicional) {
                fputcsv($handle, [
                    $adicional['fecha'],
                    '',
                    'Ayla Adicionales',
                    $adicional['descripcion'],
                    'Sin asistente',
                    'Ingreso adicional',
                    number_format($adicional['monto'], 2, ',', ''),
                    number_format($adicional['monto_bs'], 2, ',', ''),
                ], ';');
            }

            fclose($handle);
        }, 'agendas-finanzas.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function storeAylaAdicional(Request $request, TasaCambioService $tasaCambioService)
    {
        abort_unless(Auth::user()?->role === 'admin', 403, 'No tienes permisos para registrar ingresos adicionales.');

        $validated = $request->validate([
            'fecha' => 'required|date',
            'descripcion' => 'required|string|max:255',
            'monto' => 'required|numeric|gt:0',
        ]);

        $tasa = $tasaCambioService->obtener();
        AylaAdicional::create([
            'user_id' => Auth::id(),
            'fecha' => $validated['fecha'],
            'descripcion' => trim($validated['descripcion']),
            'monto' => $validated['monto'],
            'monto_bs' => round($validated['monto'] * (float) $tasa->euro_bcv, 2),
            'tasa_euro_bcv' => $tasa->euro_bcv,
        ]);

        return redirect()->back()->with('success', 'Ingreso adicional registrado correctamente.');
    }

    public function storePagoSemanal(Request $request)
    {
        abort_unless(Auth::user()?->role === 'admin', 403, 'No tienes permisos para registrar pagos semanales.');

        $validated = $request->validate([
            'especialista_id' => 'required|integer|exists:users,id',
        ]);

        $data = $this->reporteService->getReporteData('semanal');
        $especialista = collect($data['auditoria_especialistas'])->first(
            fn ($item) => (int) ($item['user_id'] ?? 0) === (int) $validated['especialista_id']
        );

        abort_unless($especialista, 422, 'El trabajador no tiene ganancias registradas esta semana.');

        $montoPagado = (float) ($especialista['comision_especialista'] ?? 0)
            + (float) ($especialista['comision_asistentes'] ?? 0);
        $montoPagadoBs = (float) ($especialista['comision_especialista_bs'] ?? 0)
            + (float) ($especialista['comision_asistentes_bs'] ?? 0);
        $inicio = now()->startOfWeek(\Carbon\Carbon::MONDAY)->toDateString();
        $fin = now()->startOfWeek(\Carbon\Carbon::MONDAY)->addDays(5)->toDateString();

        $datosPago = [
            'registrado_por_id' => Auth::id(),
            'semana_fin' => $fin,
            'monto_pagado' => round($montoPagado, 2),
            'monto_pagado_bs' => round($montoPagadoBs, 2),
            'pagado_at' => now(),
        ];

        $pagoExistente = PagoSemanalEspecialista::query()
            ->where('especialista_id', $validated['especialista_id'])
            ->whereDate('semana_inicio', $inicio)
            ->first();

        if ($pagoExistente) {
            $pagoExistente->update($datosPago);
        } else {
            PagoSemanalEspecialista::create([
                'especialista_id' => $validated['especialista_id'],
                'semana_inicio' => $inicio,
                ...$datosPago,
            ]);
        }

        return redirect()->back()->with('success', 'Pago semanal marcado como realizado.');
    }
}