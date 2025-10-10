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
        Schema::table('producto_imagenes', function (Blueprint $table) {
            $table->string('tipo')->default('galeria')->after('url_imagen'); // 'galeria', 'popup', 'email', etc.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('producto_imagenes', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
