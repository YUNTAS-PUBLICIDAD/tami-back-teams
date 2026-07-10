<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// FASE "EXPAND": solo crea la tabla nueva. No toca producto_imagenes todavía.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_email_pasos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')
                ->constrained('productos')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('paso'); // 1, 2, 3...
            $table->string('asunto', 125)->nullable();
            $table->text('mensaje')->nullable();
            $table->string('imagen_url', 125)->nullable();
            $table->string('btn_text', 100)->nullable();
            $table->string('btn_link', 255)->nullable();
            $table->string('btn_bg_color', 20)->nullable();
            $table->string('btn_text_color', 20)->nullable();
            $table->integer('delay_minutos')->default(0);
            $table->timestamps();

            $table->unique(['producto_id', 'paso'], 'producto_email_pasos_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_email_pasos');
    }
};
