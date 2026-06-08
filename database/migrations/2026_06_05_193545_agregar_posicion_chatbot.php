<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_configs', function (Blueprint $table) {
            // false = Derecha (por defecto), true = Izquierda
            $table->boolean('is_left')->default(false)->after('salute');
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_configs', function (Blueprint $table) {
            $table->dropColumn('is_left');
        });
    }
};