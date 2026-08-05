<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cita_servicio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->constrained('citas')->onDelete('cascade');
            $table->foreignId('servicio_id')->constrained('servicios')->onDelete('cascade');
            $table->decimal('precio_momento', 10, 2);
            $table->integer('duracion_momento');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cita_servicio');
    }
};