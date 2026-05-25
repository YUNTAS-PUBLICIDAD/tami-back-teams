<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_configs', function (Blueprint $table) {
            $table->string('url_icono')->nullable()->after('id'); // Agrega la columna url_icono
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_configs', function (Blueprint $table) {
            $table->dropColumn('url_icono'); // Elimina la columna si se hace rollback
        });
    }
};