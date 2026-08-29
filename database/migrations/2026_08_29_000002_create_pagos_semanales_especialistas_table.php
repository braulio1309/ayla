<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_semanales_especialistas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('especialista_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('registrado_por_id')->constrained('users')->cascadeOnDelete();
            $table->date('semana_inicio');
            $table->date('semana_fin');
            $table->decimal('monto_pagado', 14, 2);
            $table->decimal('monto_pagado_bs', 16, 2);
            $table->timestamp('pagado_at');
            $table->timestamps();

            $table->unique(['especialista_id', 'semana_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_semanales_especialistas');
    }
};