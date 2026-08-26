<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cita_servicio', function (Blueprint $table) {
            $table->foreignId('especialista_id')->nullable()->after('servicio_id')->constrained('users')->onDelete('set null');
            $table->decimal('comision_momento', 5, 2)->default(0)->after('precio_momento');
        });
    }

    public function down(): void
    {
        Schema::table('cita_servicio', function (Blueprint $table) {
            $table->dropConstrainedForeignId('especialista_id');
            $table->dropColumn('comision_momento');
        });
    }
};
