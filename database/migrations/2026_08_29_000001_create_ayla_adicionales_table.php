<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ayla_adicionales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('fecha');
            $table->string('descripcion');
            $table->decimal('monto', 14, 2);
            $table->decimal('monto_bs', 16, 2);
            $table->decimal('tasa_euro_bcv', 12, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ayla_adicionales');
    }
};