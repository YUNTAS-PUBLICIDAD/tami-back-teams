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
            // Renombramos los campos para que reflejen que ahora almacenan segundos
            $table->renameColumn('popup_start_delay_minutes', 'popup_start_delay_seconds');
            $table->renameColumn('product_popup_delay_minutes', 'product_popup_delay_seconds');
        });

        Schema::table('home_popup_settings', function (Blueprint $table) {
            // Ajustamos el valor por defecto a 60 segundos (1 minuto)
            $table->integer('popup_start_delay_seconds')->default(60)->change();
            $table->integer('product_popup_delay_seconds')->default(60)->change();
        });
        
        // Opcional: Podríamos convertir los datos existentes de minutos a segundos
        // DB::table('home_popup_settings')->update([
        //     'popup_start_delay_seconds' => DB::raw('popup_start_delay_seconds * 60'),
        //     'product_popup_delay_seconds' => DB::raw('product_popup_delay_seconds * 60'),
        // ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('home_popup_settings', function (Blueprint $table) {
            $table->renameColumn('popup_start_delay_seconds', 'popup_start_delay_minutes');
            $table->renameColumn('product_popup_delay_seconds', 'product_popup_delay_minutes');
        });

        Schema::table('home_popup_settings', function (Blueprint $table) {
            $table->integer('popup_start_delay_minutes')->default(1)->change();
            $table->integer('product_popup_delay_minutes')->default(1)->change();
        });
    }
};
