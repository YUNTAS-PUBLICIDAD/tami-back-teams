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
        Schema::create('campana_envio', function (Blueprint $table) {

    $table->id();

    $table->foreignId('campana_id')
        ->constrained('campanas')
        ->cascadeOnDelete();

    // cliente al que se envía
    $table->string('telefono');

    // estado del envío
    $table->enum('estado', [
        'pendiente',
        'enviado',
        'fallido'
    ])->default('pendiente');

    // opcional: guardar respuesta API
    $table->text('respuesta')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campana_envio');
    }
};