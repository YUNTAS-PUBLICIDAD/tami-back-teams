<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * FASE "CONTRACT" — NO EJECUTAR hasta que:
 *   1) El comando `migrate:producto-imagenes-data` se haya corrido en producción (sin --dry-run).
 *   2) Los conteos de origen/destino se hayan verificado manualmente.
 *   3) El código de la aplicación (controllers, jobs, vistas) ya lea de
 *      `producto_whatsapp_pasos` y `producto_email_pasos` en vez de las
 *      columnas viejas de `producto_imagenes`.
 *   4) Haya pasado al menos un ciclo de despliegue estable con el código nuevo.
 *
 * La fecha de esta migración (2026_07_18) es solo una referencia de que debe
 * ir DESPUÉS de la fase de migración de datos; ajústala al momento real en
 * que decidan aplicarla.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Borra primero las filas ya migradas de producto_imagenes (whatsapp/email),
        // dado que su información ahora vive en las tablas nuevas.
        DB::table('producto_imagenes')
            ->where('tipo', 'whatsapp')
            ->orWhere('tipo', 'like', 'email%')
            ->delete();

        Schema::table('producto_imagenes', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_image_url_2',
                'whatsapp_image_url_3',
                'asunto',
                'whatsapp_mensaje',
                'whatsapp_mensaje_2',
                'whatsapp_mensaje_3',
                'whatsapp_time_1',
                'whatsapp_time_2',
                'whatsapp_time_3',
                'email_mensaje',
                'email_btn_text',
                'email_btn_link',
                'email_btn_bg_color',
                'email_btn_text_color',
                'delay_minutes',
            ]);
        });
    }

    public function down(): void
    {
        // El down() no puede recuperar los datos eliminados; solo restaura la estructura.
        Schema::table('producto_imagenes', function (Blueprint $table) {
            $table->string('whatsapp_image_url_2', 125)->nullable();
            $table->string('whatsapp_image_url_3', 125)->nullable();
            $table->string('asunto', 125)->nullable();
            $table->text('whatsapp_mensaje')->nullable();
            $table->text('whatsapp_mensaje_2')->nullable();
            $table->text('whatsapp_mensaje_3')->nullable();
            $table->integer('whatsapp_time_1')->default(0);
            $table->integer('whatsapp_time_2')->default(0);
            $table->integer('whatsapp_time_3')->default(0);
            $table->text('email_mensaje')->nullable();
            $table->string('email_btn_text', 100)->nullable();
            $table->string('email_btn_link', 255)->nullable();
            $table->string('email_btn_bg_color', 20)->nullable();
            $table->string('email_btn_text_color', 20)->nullable();
            $table->integer('delay_minutes')->default(0);
        });
    }
};
