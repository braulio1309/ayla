<?php

use App\Models\Cita;
use App\Services\MetaWhatsAppService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:send-whatsapp-reminders')->everyFifteenMinutes();

Artisan::command('app:send-whatsapp-reminders', function () {
    $service = app(MetaWhatsAppService::class);
    $hoy = now();

    Cita::with(['paciente', 'especialista'])
        ->whereDate('fecha', '>=', $hoy->toDateString())
        ->where('estado', '!=', 'cancelado')
        ->get()
        ->each(function (Cita $cita) use ($service, $hoy) {
            $service->sendCitaReminderIfNeeded($cita, $hoy);
        });
})->purpose('Enviar recordatorios de WhatsApp para citas próximas');
