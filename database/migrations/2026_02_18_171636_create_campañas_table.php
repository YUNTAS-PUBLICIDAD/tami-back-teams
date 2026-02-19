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
        Schema::create('campañas', function (Blueprint $table){
            $table->id();
            $table->string('nombre');
            //relacion con productos
            $table->foreingId('producto_id')
                ->constrained('productos')
                ->onDelete('cascade');
            //contenido personalizado
            $table->text('contenido_personalizado')->nullable();
            $table->string('imagen_path')->nullable(); //ruta de la imagen personalizada
            //estado de la campaña
            $table->enum('estado',['borrador','enviado','completado'])->default('borrador');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campañas');
    }
};
