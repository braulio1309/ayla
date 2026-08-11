<?php

namespace App\Notifications;

use App\Models\Cita;
use App\Models\Paciente;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NuevaCitaAsignada extends Notification
{
    use Queueable;

    protected $cita;
    protected $paciente;
    protected $servicios;

    public function __construct(Cita $cita, Paciente $paciente, $servicios)
    {
        $this->cita = $cita;
        $this->paciente = $paciente;
        $this->servicios = $servicios;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message' => 'Se te asignó una nueva cita para ' . $this->paciente->nombre . ' el ' . $this->cita->fecha->format('d/m/Y') . ' a las ' . $this->cita->hora_inicio,
            'cita_id' => $this->cita->id,
            'paciente' => $this->paciente->nombre,
            'fecha' => $this->cita->fecha->format('Y-m-d'),
            'hora' => $this->cita->hora_inicio,
        ];
    }
}
