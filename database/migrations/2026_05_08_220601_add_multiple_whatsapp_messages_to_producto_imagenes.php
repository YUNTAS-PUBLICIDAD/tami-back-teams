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
        Schema::table('producto_imagenes', function (Blueprint $table) {
            $table->text('whatsapp_mensaje_2')->nullable()->after('whatsapp_mensaje');
            $table->text('whatsapp_mensaje_3')->nullable()->after('whatsapp_mensaje_2');
            $table->integer('whatsapp_time_1')->default(0)->after('whatsapp_mensaje_3');
            $table->integer('whatsapp_time_2')->default(0)->after('whatsapp_time_1');
            $table->integer('whatsapp_time_3')->default(0)->after('whatsapp_time_2');
            $table->string('whatsapp_image_url_2')->nullable()->after('url_imagen');
            $table->string('whatsapp_image_url_3')->nullable()->after('whatsapp_image_url_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('producto_imagenes', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_mensaje_2',
                'whatsapp_mensaje_3',
                'whatsapp_time_1',
                'whatsapp_time_2',
                'whatsapp_time_3',
                'whatsapp_image_url_2',
                'whatsapp_image_url_3',
            ]);
        });
    }
};
