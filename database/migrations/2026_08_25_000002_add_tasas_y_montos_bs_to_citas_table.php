<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->decimal('tasa_dolar_bcv', 12, 4)->nullable()->after('monto_total');
            $table->decimal('tasa_euro_bcv', 12, 4)->nullable()->after('tasa_dolar_bcv');
            $table->decimal('monto_total_bs', 14, 2)->nullable()->after('tasa_euro_bcv');
        });

        Schema::table('cita_servicio', function (Blueprint $table) {
            $table->decimal('monto_bs_momento', 14, 2)->nullable()->after('precio_momento');
        });
    }

    public function down(): void
    {
        Schema::table('cita_servicio', function (Blueprint $table) {
            $table->dropColumn('monto_bs_momento');
        });

        Schema::table('citas', function (Blueprint $table) {
            $table->dropColumn(['tasa_dolar_bcv', 'tasa_euro_bcv', 'monto_total_bs']);
        });
    }
};
