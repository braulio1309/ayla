<?php

namespace App\Services;

use App\Models\Servicio;

class ServicioService
{
    public function getServiciosData(?string $search = null, ?string $categoria = null): array
    {
        $query = Servicio::query();

        if ($search) {
            $query->where('nombre', 'like', "%{$search}%")
                ->orWhere('descripcion', 'like', "%{$search}%");
        }

        if ($categoria) {
            $query->where('categoria', $categoria);
        }

        $servicios = $query->get()->map(function ($s) {
            return [
                'id' => $s->id,
                'nombre' => $s->nombre,
                'categoria' => $s->categoria,
                'precio_base' => (float) ($s->precio_base ?? $s->precio),
                'duracion_min' => (int) ($s->duracion_min ?? $s->duracion),
                'descripcion' => $s->descripcion,
            ];
        })->values()->all();

        return [
            'filters' => [
                'search' => $search,
                'categoria' => $categoria,
            ],
            'servicios' => $servicios,
            'categorias' => ['Cosmiatría', 'Masajes & Spa', 'Manos / Pies', 'Estética General'],
        ];
    }
}
