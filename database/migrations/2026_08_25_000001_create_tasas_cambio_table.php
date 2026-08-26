<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasas_cambio', function (Blueprint $table) {
            $table->id();
            $table->decimal('dolar_bcv', 12, 4);
            $table->decimal('euro_bcv', 12, 4);
            $table->timestamp('actualizada_en');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasas_cambio');
    }
};
