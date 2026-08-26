<?php

namespace App\Services;

use App\Models\Cita;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MetaWhatsAppService
{
    public function sendCitaCreadaMessage(Cita $cita): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        if (! $cita->paciente || blank($cita->paciente->telefono)) {
            return false;
        }

        if ($cita->whatsapp_creacion_enviado_at) {
            return false;
        }

        $mensaje = sprintf(
            'Hola %s, tu cita con %s queda agendada para el %s a las %s. Gracias por confiar en Ayla.',
            $cita->paciente->nombre,
            $cita->especialista?->name ?? 'nuestro equipo',
            $cita->fecha?->format('d/m/Y') ?? 'pronto',
            $cita->hora_inicio
        );

        $enviado = $this->sendText($cita->paciente->telefono, $mensaje);

        if ($enviado) {
            $cita->update([
                'whatsapp_creacion_enviado_at' => now(),
            ]);
        }

        return $enviado;
    }

    public function sendCitaReminderIfNeeded(Cita $cita, ?Carbon $now = null): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        if (! $cita->paciente || blank($cita->paciente->telefono)) {
            return false;
        }

        if (in_array($cita->estado, ['cancelado', 'completado'], true)) {
            return false;
        }

        $now = $now ?: now();
        $fechaHoraCita = $cita->fecha
            ? Carbon::parse($cita->fecha->format('Y-m-d') . ' ' . $cita->hora_inicio)
            : null;

        if (! $fechaHoraCita) {
            return false;
        }

        $diferenciaMinutos = (int) $fechaHoraCita->diffInMinutes($now, false);
        $ventanaMinutos = (int) config('services.meta_whatsapp.reminder_window_minutes', 1440);
        $margenMinutos = (int) config('services.meta_whatsapp.reminder_margin_minutes', 15);

        if ($diferenciaMinutos > $ventanaMinutos || $diferenciaMinutos < $margenMinutos) {
            return false;
        }

        if ($cita->whatsapp_recordatorio_enviado_at) {
            $ultimaHora = $cita->whatsapp_recordatorio_enviado_at->copy()->addMinutes((int) config('services.meta_whatsapp.reminder_repeat_minutes', 180));
            if ($now->lessThan($ultimaHora)) {
                return false;
            }
        }

        $mensaje = sprintf(
            'Hola %s, te recordamos que tu cita con %s es el %s a las %s. Te esperamos en Ayla.',
            $cita->paciente->nombre,
            $cita->especialista?->name ?? 'nuestro equipo',
            $cita->fecha->format('d/m/Y'),
            $cita->hora_inicio
        );

        $enviado = $this->sendText($cita->paciente->telefono, $mensaje);

        if ($enviado) {
            $cita->update([
                'whatsapp_recordatorio_enviado_at' => $now,
            ]);
        }

        return $enviado;
    }

    public function sendText(string $telefono, string $mensaje): bool
    {
        $telefonoFormateado = $this->normalizarTelefono($telefono);

        if (! $telefonoFormateado) {
            return false;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.meta_whatsapp.token'),
        ])->post(
            'https://graph.facebook.com/v20.0/' . config('services.meta_whatsapp.phone_number_id') . '/messages',
            [
                'messaging_product' => 'whatsapp',
                'to' => $telefonoFormateado,
                'type' => 'text',
                'text' => [
                    'body' => $mensaje,
                ],
            ]
        );

        if (! $response->successful()) {
            \Log::error('Meta WhatsApp error', [
                'status' => $response->status(),
                'body' => $response->json(),
                'telefono' => $telefonoFormateado,
            ]);

            return false;
        }

        return true;
    }

    protected function isConfigured(): bool
    {
        return ! empty(config('services.meta_whatsapp.token'))
            && ! empty(config('services.meta_whatsapp.phone_number_id'));
    }

    protected function normalizarTelefono(string $telefono): ?string
    {
        $telefono = trim((string) $telefono);
        $telefono = preg_replace('/[^\d+]/', '', $telefono);

        if ($telefono === '') {
            return null;
        }

        $telefono = str_replace('+', '', $telefono);
        $telefono = preg_replace('/^00/', '', $telefono);

        $codigoPais = trim((string) config('services.meta_whatsapp.country_code', '57'));
        $codigoPais = preg_replace('/\D+/', '', $codigoPais);

        if (strlen($telefono) === 10 && $codigoPais !== '') {
            $telefono = $codigoPais . $telefono;
        }

        if (strlen($telefono) < 8) {
            return null;
        }

        return '+' . ltrim($telefono, '+');
    }
}
