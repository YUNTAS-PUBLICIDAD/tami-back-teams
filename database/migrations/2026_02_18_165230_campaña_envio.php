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
            $table->unsignedBigInteger('campana_id');
            $table->unsignedBigInteger('envio_id');
            $table->timestamps();

            $table->foreign('campana_id')->references('id')->on('campanas')->onDelete('cascade');
            $table->foreign('envio_id')->references('id')->on('envios')->onDelete('cascade');
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
