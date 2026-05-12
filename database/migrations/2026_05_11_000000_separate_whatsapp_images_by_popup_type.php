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
            // Columnas para Inicio (usar las existentes + nuevas para mensaje 2 y 3)
            $table->string('whatsapp_image_url_inicio')->nullable()->after('whatsapp_image_url');
            $table->string('whatsapp_image_url_2_inicio')->nullable()->after('whatsapp_image_url_inicio');
            $table->string('whatsapp_image_url_3_inicio')->nullable()->after('whatsapp_image_url_2_inicio');

            // Columnas para Producto
            $table->string('whatsapp_image_url_producto')->nullable()->after('whatsapp_image_url_3_inicio');
            $table->string('whatsapp_image_url_2_producto')->nullable()->after('whatsapp_image_url_producto');
            $table->string('whatsapp_image_url_3_producto')->nullable()->after('whatsapp_image_url_2_producto');

            // Mensajes separados por tipo si es necesario (opcional)
            $table->text('whatsapp_message_inicio')->nullable()->after('whatsapp_message');
            $table->text('whatsapp_message_2_inicio')->nullable()->after('whatsapp_message_inicio');
            $table->text('whatsapp_message_3_inicio')->nullable()->after('whatsapp_message_2_inicio');

            $table->text('whatsapp_message_producto')->nullable()->after('whatsapp_message_3_inicio');
            $table->text('whatsapp_message_2_producto')->nullable()->after('whatsapp_message_producto');
            $table->text('whatsapp_message_3_producto')->nullable()->after('whatsapp_message_2_producto');

            // Tiempos separados por tipo
            $table->unsignedInteger('whatsapp_time_1_inicio')->nullable()->after('whatsapp_time_3');
            $table->unsignedInteger('whatsapp_time_2_inicio')->nullable()->after('whatsapp_time_1_inicio');
            $table->unsignedInteger('whatsapp_time_3_inicio')->nullable()->after('whatsapp_time_2_inicio');

            $table->unsignedInteger('whatsapp_time_1_producto')->nullable()->after('whatsapp_time_3_inicio');
            $table->unsignedInteger('whatsapp_time_2_producto')->nullable()->after('whatsapp_time_1_producto');
            $table->unsignedInteger('whatsapp_time_3_producto')->nullable()->after('whatsapp_time_2_producto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('home_popup_settings', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_image_url_inicio',
                'whatsapp_image_url_2_inicio',
                'whatsapp_image_url_3_inicio',
                'whatsapp_image_url_producto',
                'whatsapp_image_url_2_producto',
                'whatsapp_image_url_3_producto',
                'whatsapp_message_inicio',
                'whatsapp_message_2_inicio',
                'whatsapp_message_3_inicio',
                'whatsapp_message_producto',
                'whatsapp_message_2_producto',
                'whatsapp_message_3_producto',
                'whatsapp_time_1_inicio',
                'whatsapp_time_2_inicio',
                'whatsapp_time_3_inicio',
                'whatsapp_time_1_producto',
                'whatsapp_time_2_producto',
                'whatsapp_time_3_producto',
            ]);
        });
    }
};
