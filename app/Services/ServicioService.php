<?php

namespace App\Services;

use App\Models\Servicio;
use App\Models\User;

class ServicioService
{
    public function getServiciosData(?string $search = null, ?string $categoria = null): array
    {
        $query = Servicio::with('especialistas');

        if ($search) {
            $query = $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        if ($categoria) {
            $query = $query->where('categoria', $categoria);
        }

        $servicios = $query->get()->map(function ($s) {
            return [
                'id' => $s->id,
                'nombre' => $s->nombre,
                'categoria' => $s->categoria,
                'precio_base' => (float) ($s->precio_base ?? 0),
                'duracion_min' => (int) ($s->duracion_min ?? 0),
                'descripcion' => $s->descripcion,
                'especialistas' => $s->especialistas->map(function ($especialista) use ($s) {
                    return [
                        'id' => $especialista->id,
                        'name' => $especialista->name,
                        'precio_especialista' => (float) ($especialista->pivot->precio_especialista ?? $s->precio_base ?? 0),
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        $especialistas = User::where('role', 'especialista')
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
            ])
            ->all();

        return [
            'filters' => [
                'search' => $search,
                'categoria' => $categoria,
            ],
            'servicios' => $servicios,
            'categorias' => ['Cosmiatría', 'Masajes & Spa', 'Manos / Pies', 'Estética General'],
            'especialistas' => $especialistas,
        ];
    }
}
