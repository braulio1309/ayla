<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->timestamp('whatsapp_creacion_enviado_at')->nullable()->after('observaciones');
            $table->timestamp('whatsapp_recordatorio_enviado_at')->nullable()->after('whatsapp_creacion_enviado_at');
        });
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_creacion_enviado_at', 'whatsapp_recordatorio_enviado_at']);
        });
    }
};
