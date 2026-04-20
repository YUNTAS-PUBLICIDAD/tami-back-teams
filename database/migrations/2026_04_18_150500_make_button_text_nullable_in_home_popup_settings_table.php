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
        Schema::table('home_popup_settings', function (Blueprint $table) {
            // Aseguramos que el campo button_text sea nullable, como solicitó el usuario.
            // Si por alguna razón no existe, lo agregamos; si existe, lo modificamos.
            if (Schema::hasColumn('home_popup_settings', 'button_text')) {
                $table->string('button_text')->nullable()->change();
            } else {
                $table->string('button_text')->nullable()->after('subtitle');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('home_popup_settings', function (Blueprint $table) {
            $table->string('button_text')->nullable(false)->change();
        });
    }
};
