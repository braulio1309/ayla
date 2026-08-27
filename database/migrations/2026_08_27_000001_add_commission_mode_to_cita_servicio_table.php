<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cita_servicio', function (Blueprint $table) {
            $table->string('comision_tipo', 12)->default('porcentaje')->after('comision_momento');
            $table->decimal('comision_monto', 10, 2)->default(0)->after('comision_tipo');
        });
    }

    public function down(): void
    {
        Schema::table('cita_servicio', function (Blueprint $table) {
            $table->dropColumn(['comision_tipo', 'comision_monto']);
        });
    }
};
