<?php

namespace App\Services;

use App\Models\TasaCambio;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TasaCambioService
{
    private const DOLAR_URL = 'https://ve.dolarapi.com/v1/dolares/oficial';
    private const EURO_URL = 'https://ve.dolarapi.com/v1/euros/oficial';

    public function obtener(bool $actualizar = false): TasaCambio
    {
        $actual = TasaCambio::query()->latest('actualizada_en')->first();

        if (!$actualizar && $actual && $actual->actualizada_en?->isToday()) {
            return $actual;
        }

        try {
            $dolar = $this->leerTasa(self::DOLAR_URL);
            $euro = $this->leerTasa(self::EURO_URL);

            return TasaCambio::create([
                'dolar_bcv' => $dolar,
                'euro_bcv' => $euro,
                'actualizada_en' => now(),
            ]);
        } catch (RuntimeException $exception) {
            if ($actual) {
                return $actual;
            }

            throw $exception;
        }
    }

    private function leerTasa(string $url): float
    {
        $response = Http::timeout(8)->get($url);

        if (!$response->successful()) {
            throw new RuntimeException('No se pudo consultar la tasa de cambio.');
        }

        $data = $response->json();
        $valor = $data['promedio'] ?? $data['valor'] ?? $data['price'] ?? null;

        if (!is_numeric($valor) || (float) $valor <= 0) {
            throw new RuntimeException('La API devolvió una tasa inválida.');
        }

        return (float) $valor;
    }
}
