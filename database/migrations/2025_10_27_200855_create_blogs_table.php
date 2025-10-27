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
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->nullable()->constrained('productos');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('titulo', 255)->nullable();
            $table->string('link', 255)->unique()->nullable();
            $table->text('subtitulo1')->nullable();
            $table->text('subtitulo2')->nullable();
            $table->string('video_url', 125)->nullable();
            $table->string('video_titulo', 40)->nullable();
            $table->string('miniatura', 500)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};
