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
        Schema::create('campaign_message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campana_id')
                ->constrained('campanas')
                ->onDelete('cascade');
            $table->foreignId('cliente_id')
                ->constrained('clientes')
                ->onDelete('cascade');
            $table->string('phone', 20);
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Índices para queries frecuentes
            $table->index(['cliente_id', 'status']);
            $table->index(['campana_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_message_logs');
    }
};
