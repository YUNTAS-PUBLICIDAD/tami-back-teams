<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// FASE "EXPAND": solo crea la tabla nueva. No toca producto_imagenes todavía.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_whatsapp_pasos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')
                ->constrained('productos')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('paso'); // 1, 2, 3...
            $table->text('mensaje')->nullable();
            $table->string('imagen_url', 125)->nullable();
            $table->integer('delay_minutos')->default(0);
            $table->timestamps();

            $table->unique(['producto_id', 'paso'], 'producto_whatsapp_pasos_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_whatsapp_pasos');
    }
};
