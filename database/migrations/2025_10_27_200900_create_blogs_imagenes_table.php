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
        Schema::create('blogs_imagenes', function (Blueprint $table) {
            $table->id();
            $table->string('ruta_imagen', 255)->nullable();
            $table->string('text_alt', 255)->nullable();
            $table->foreignId('blog_id')->constrained('blogs');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blogs_imagenes');
    }
};
