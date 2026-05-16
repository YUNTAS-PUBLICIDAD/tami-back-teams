<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrar datos antiguos a las nuevas columnas
        // Por defecto, los datos antiguos irán a "Inicio" ya que la configuración global era para Inicio

        DB::table('home_popup_settings')->update([
            // Copiar datos antiguos a Inicio
            'whatsapp_message_inicio' => DB::raw('whatsapp_message'),
            'whatsapp_message_2_inicio' => DB::raw('whatsapp_message_2'),
            'whatsapp_message_3_inicio' => DB::raw('whatsapp_message_3'),
            'whatsapp_image_url_inicio' => DB::raw('whatsapp_image_url'),
            'whatsapp_image_url_2_inicio' => DB::raw('whatsapp_image_url_2'),
            'whatsapp_image_url_3_inicio' => DB::raw('whatsapp_image_url_3'),
            'whatsapp_time_1_inicio' => DB::raw('whatsapp_time_1'),
            'whatsapp_time_2_inicio' => DB::raw('whatsapp_time_2'),
            'whatsapp_time_3_inicio' => DB::raw('whatsapp_time_3'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hacer nada en down, mantenemos los datos
    }
};
