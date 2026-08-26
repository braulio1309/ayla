<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->foreignId('asistente_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->decimal('comision_asistente_porcentaje', 5, 2)->nullable()->after('asistente_id');
        });
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropForeign(['asistente_id']);
            $table->dropColumn(['asistente_id', 'comision_asistente_porcentaje']);
        });
    }
};
