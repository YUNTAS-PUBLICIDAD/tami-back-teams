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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->decimal('precio', 12, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->string('link', 500)->unique()->nullable();
            $table->string('video_url', 255)->nullable();
            $table->string('alto', 50)->nullable();
            $table->string('largo', 50)->nullable();
            $table->string('ancho', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
