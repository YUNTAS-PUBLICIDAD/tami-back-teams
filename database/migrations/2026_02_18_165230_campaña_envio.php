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
        Schema::create('campaña_envio', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaña_id');
            $table->unsignedBigInteger('envio_id');
            $table->timestamps();

            $table->foreign('campaña_id')->references('id')->on('campañas')->onDelete('cascade');
            $table->foreign('envio_id')->references('id')->on('envios')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaña_envio');
    }
};
