<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Reduce home_popup_settings a configuración global de un solo popup.
// Las secuencias de WhatsApp y Email pasan a tablas hijas (popup_whatsapp_steps / popup_email_steps)
// y se eliminan ~55 columnas muertas/redundantes (variantes _inicio/_producto, alias, _2/_3).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('popup_whatsapp_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('popup_setting_id')
                ->constrained('home_popup_settings')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('sequence'); // 1, 2, 3...
            $table->text('message')->nullable();
            $table->string('image_url')->nullable();
            $table->integer('delay_minutes')->default(0); // -1 = desactivado
            $table->timestamps();

            $table->unique(['popup_setting_id', 'sequence'], 'popup_whatsapp_steps_unique');
        });

        Schema::create('popup_email_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('popup_setting_id')
                ->constrained('home_popup_settings')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('sequence'); // 1, 2, 3...
            $table->string('subject')->nullable();
            $table->text('message')->nullable();
            $table->string('image_url')->nullable();
            $table->string('btn_text')->nullable();
            $table->string('btn_link')->nullable();
            $table->string('btn_bg_color', 20)->nullable();
            $table->string('btn_text_color', 20)->nullable();
            $table->integer('delay_minutes')->default(0); // -1 = desactivado
            $table->timestamps();

            $table->unique(['popup_setting_id', 'sequence'], 'popup_email_steps_unique');
        });

        // Copiar datos existentes a las tablas hijas
        $settings = DB::table('home_popup_settings')->get();

        foreach ($settings as $setting) {
            $whatsapp = [
                1 => [
                    'message' => $setting->whatsapp_message_inicio ?? $setting->whatsapp_message,
                    'image_url' => $setting->whatsapp_image_url_inicio ?? $setting->whatsapp_image_url,
                    'delay_minutes' => $setting->whatsapp_time_1_inicio ?? $setting->whatsapp_time_1,
                ],
                2 => [
                    'message' => $setting->whatsapp_message_2_inicio ?? $setting->whatsapp_message_2,
                    'image_url' => $setting->whatsapp_image_url_2_inicio ?? $setting->whatsapp_image_url_2,
                    'delay_minutes' => $setting->whatsapp_time_2_inicio ?? $setting->whatsapp_time_2,
                ],
                3 => [
                    'message' => $setting->whatsapp_message_3_inicio ?? $setting->whatsapp_message_3,
                    'image_url' => $setting->whatsapp_image_url_3_inicio ?? $setting->whatsapp_image_url_3,
                    'delay_minutes' => $setting->whatsapp_time_3_inicio ?? $setting->whatsapp_time_3,
                ],
            ];

            foreach ($whatsapp as $seq => $data) {
                DB::table('popup_whatsapp_steps')->insert([
                    'popup_setting_id' => $setting->id,
                    'sequence' => $seq,
                    'message' => $data['message'],
                    'image_url' => $data['image_url'],
                    'delay_minutes' => (int) ($data['delay_minutes'] ?? 0),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $emails = [
                1 => [
                    'subject' => $setting->email_subject,
                    'message' => $setting->email_message,
                    'image_url' => $setting->email_image_url,
                    'btn_text' => $setting->email_btn_text,
                    'btn_link' => $setting->email_btn_link,
                    'btn_bg_color' => $setting->email_btn_bg_color,
                    'btn_text_color' => $setting->email_btn_text_color,
                    'delay_minutes' => $setting->email_send_delay_minutes,
                ],
                2 => [
                    'subject' => $setting->email_subject_2,
                    'message' => $setting->email_message_2,
                    'image_url' => $setting->email_image_url_2,
                    'btn_text' => $setting->email_btn_text_2,
                    'btn_link' => $setting->email_btn_link_2,
                    'btn_bg_color' => $setting->email_btn_bg_color_2,
                    'btn_text_color' => $setting->email_btn_text_color_2,
                    'delay_minutes' => $setting->email_send_delay_minutes_2,
                ],
                3 => [
                    'subject' => $setting->email_subject_3,
                    'message' => $setting->email_message_3,
                    'image_url' => $setting->email_image_url_3,
                    'btn_text' => $setting->email_btn_text_3,
                    'btn_link' => $setting->email_btn_link_3,
                    'btn_bg_color' => $setting->email_btn_bg_color_3,
                    'btn_text_color' => $setting->email_btn_text_color_3,
                    'delay_minutes' => $setting->email_send_delay_minutes_3,
                ],
            ];

            foreach ($emails as $seq => $data) {
                DB::table('popup_email_steps')->insert([
                    'popup_setting_id' => $setting->id,
                    'sequence' => $seq,
                    'subject' => $data['subject'],
                    'message' => $data['message'],
                    'image_url' => $data['image_url'],
                    'btn_text' => $data['btn_text'],
                    'btn_link' => $data['btn_link'],
                    'btn_bg_color' => $data['btn_bg_color'],
                    'btn_text_color' => $data['btn_text_color'],
                    'delay_minutes' => (int) ($data['delay_minutes'] ?? 0),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Eliminar columnas redundantes / muertas
        Schema::table('home_popup_settings', function (Blueprint $table) {
            $table->dropColumn([
                'title',
                'subtitle',
                'popup_image2_url',
                // WhatsApp genéricos
                'whatsapp_message',
                'whatsapp_message_2',
                'whatsapp_message_3',
                'whatsapp_time_1',
                'whatsapp_time_2',
                'whatsapp_time_3',
                'whatsapp_image_url',
                'whatsapp_image_url_2',
                'whatsapp_image_url_3',
                // WhatsApp Inicio
                'whatsapp_message_inicio',
                'whatsapp_message_2_inicio',
                'whatsapp_message_3_inicio',
                'whatsapp_time_1_inicio',
                'whatsapp_time_2_inicio',
                'whatsapp_time_3_inicio',
                'whatsapp_image_url_inicio',
                'whatsapp_image_url_2_inicio',
                'whatsapp_image_url_3_inicio',
                // WhatsApp Producto (muertas)
                'whatsapp_message_producto',
                'whatsapp_message_2_producto',
                'whatsapp_message_3_producto',
                'whatsapp_time_1_producto',
                'whatsapp_time_2_producto',
                'whatsapp_time_3_producto',
                'whatsapp_image_url_producto',
                'whatsapp_image_url_2_producto',
                'whatsapp_image_url_3_producto',
                // Email 1
                'email_subject',
                'email_message',
                'email_image_url',
                'email_btn_text',
                'email_btn_link',
                'email_btn_bg_color',
                'email_btn_text_color',
                'email_send_delay_minutes',
                // Email 2
                'email_subject_2',
                'email_message_2',
                'email_image_url_2',
                'email_btn_text_2',
                'email_btn_link_2',
                'email_btn_bg_color_2',
                'email_btn_text_color_2',
                'email_send_delay_minutes_2',
                // Email 3
                'email_subject_3',
                'email_message_3',
                'email_image_url_3',
                'email_btn_text_3',
                'email_btn_link_3',
                'email_btn_bg_color_3',
                'email_btn_text_color_3',
                'email_send_delay_minutes_3',
                // Otros
                'popup_mobile_image_count',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('popup_whatsapp_steps');
        Schema::dropIfExists('popup_email_steps');
    }
};