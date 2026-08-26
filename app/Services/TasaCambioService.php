<?php

namespace App\Services;

use App\Models\TasaCambio;
use Illuminate\Support\Facades\Http;
use Throwable;

class TasaCambioService
{
    private const DOLAR_URL = 'https://ve.dolarapi.com/v1/dolares/oficial';
    private const EURO_URL = 'https://ve.dolarapi.com/v1/euros/oficial';

    public function obtener(bool $actualizar = true): TasaCambio
    {
        $actual = TasaCambio::query()->latest('actualizada_en')->latest('id')->first();

        if ($actualizar) {
            try {
                $dolar = $this->leerTasa(self::DOLAR_URL);
                $euro = $this->leerTasa(self::EURO_URL);

                if (!$actual || (float) $actual->dolar_bcv !== (float) $dolar || (float) $actual->euro_bcv !== (float) $euro) {
                    $actual = TasaCambio::create([
                        'dolar_bcv' => $dolar,
                        'euro_bcv' => $euro,
                        'actualizada_en' => now(),
                    ]);
                } else {
                    $actual->update([
                        'actualizada_en' => now(),
                    ]);
                }
            } catch (Throwable $exception) {
                if ($actual) {
                    return $actual;
                }
            }
        }

        if (!$actual) {
            try {
                $dolar = $this->leerTasa(self::DOLAR_URL);
                $euro = $this->leerTasa(self::EURO_URL);

                $actual = TasaCambio::create([
                    'dolar_bcv' => $dolar,
                    'euro_bcv' => $euro,
                    'actualizada_en' => now(),
                ]);
            } catch (Throwable $e) {
                $actual = TasaCambio::create([
                    'dolar_bcv' => 36.5000,
                    'euro_bcv' => 40.0000,
                    'actualizada_en' => now(),
                ]);
            }
        }

        return $actual;
    }

    private function leerTasa(string $url): float
    {
        $response = Http::timeout(5)->get($url);

        if (!$response->successful()) {
            throw new \RuntimeException('No se pudo consultar la tasa de cambio.');
        }

        $data = $response->json();
        $valor = $data['promedio'] ?? $data['valor'] ?? $data['price'] ?? null;

        if (!is_numeric($valor) || (float) $valor <= 0) {
            throw new \RuntimeException('La API devolvió una tasa inválida.');
        }

        return (float) $valor;
    }
}
