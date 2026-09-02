<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->boolean('cobro_anticipado')->default(false)->after('monto_total');
            $table->unsignedInteger('sesiones_cobradas')->default(1)->after('cobro_anticipado');
            $table->boolean('excluir_finanzas')->default(false)->after('sesiones_cobradas');
        });
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropColumn(['cobro_anticipado', 'sesiones_cobradas', 'excluir_finanzas']);
        });
    }
};