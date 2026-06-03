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
        Schema::table('chatbot_configs', function (Blueprint $table) {
            // Separados de forma independiente (Mide 7 caracteres exactos cada uno)
            $table->string('color_inicial', 7)->nullable()->after('url_icono');
            $table->string('color_final', 7)->nullable()->after('color_inicial');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chatbot_configs', function (Blueprint $table) {
            $table->dropColumn(['color_inicial', 'color_final']);
        });
    }
};