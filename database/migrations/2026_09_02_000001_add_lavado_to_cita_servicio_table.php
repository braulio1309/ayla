<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cita_servicio', function (Blueprint $table) {
            $table->boolean('requiere_lavado')->default(false)->after('comision_monto');
            $table->foreignId('lavado_especialista_id')->nullable()->after('requiere_lavado')->constrained('users')->nullOnDelete();
            $table->decimal('lavado_monto', 10, 2)->default(0)->after('lavado_especialista_id');
        });
    }

    public function down(): void
    {
        Schema::table('cita_servicio', function (Blueprint $table) {
            $table->dropForeign(['lavado_especialista_id']);
            $table->dropColumn(['requiere_lavado', 'lavado_especialista_id', 'lavado_monto']);
        });
    }
};