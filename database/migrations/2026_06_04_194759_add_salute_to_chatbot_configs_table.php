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
            // Usamos text en lugar de string por si el admin escribe un saludo muy largo
            $table->text('salute')->nullable()->after('url_icono'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chatbot_configs', function (Blueprint $table) {
            //
        });
    }
};
