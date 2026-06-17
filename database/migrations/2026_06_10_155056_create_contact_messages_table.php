<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            
            // 🛠️ Agregamos los campos exactos del formulario y del modelo
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone');
            $table->string('district')->nullable();       // nullable por si el usuario no pone distrito
            $table->string('request_detail')->nullable(); // nullable por si no elige detalle
            $table->text('message');                      // text porque los mensajes pueden ser largos
            
            $table->timestamps(); // Esto creará created_at y updated_at automáticamente
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
