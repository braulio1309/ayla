<?php

namespace Tests\Feature;

use App\Models\TasaCambio;
use App\Services\TasaCambioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TasaCambioTest extends TestCase
{
    use RefreshDatabase;

    public function test_guarda_las_tasas_promedio_de_dolar_y_euro(): void
    {
        Http::fake([
            'https://ve.dolarapi.com/v1/dolares/oficial' => Http::response([
                'moneda' => 'USD',
                'promedio' => 150.123456,
                'fechaActualizacion' => '2026-08-25T00:00:00-04:00',
            ]),
            'https://ve.dolarapi.com/v1/euros/oficial' => Http::response([
                'moneda' => 'EUR',
                'promedio' => 916.02670993,
                'fechaActualizacion' => '2026-08-25T00:00:00-04:00',
            ]),
        ]);

        $tasa = app(TasaCambioService::class)->obtener(true);

        $this->assertSame(150.1235, (float) $tasa->dolar_bcv);
        $this->assertSame(916.0267, (float) $tasa->euro_bcv);
        $this->assertDatabaseHas('tasas_cambio', [
            'id' => $tasa->id,
            'dolar_bcv' => 150.1235,
            'euro_bcv' => 916.0267,
        ]);
    }
}
