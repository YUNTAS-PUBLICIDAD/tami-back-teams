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
            $table->text('whatsapp_message_2')->nullable()->after('whatsapp_message');
            $table->text('whatsapp_message_3')->nullable()->after('whatsapp_message_2');
            $table->integer('whatsapp_time_1')->default(0)->after('whatsapp_message_3');
            $table->integer('whatsapp_time_2')->default(0)->after('whatsapp_time_1');
            $table->integer('whatsapp_time_3')->default(0)->after('whatsapp_time_2');
            $table->string('whatsapp_image_url_2')->nullable()->after('whatsapp_image_url');
            $table->string('whatsapp_image_url_3')->nullable()->after('whatsapp_image_url_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('home_popup_settings', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_message_2',
                'whatsapp_message_3',
                'whatsapp_time_1',
                'whatsapp_time_2',
                'whatsapp_time_3',
                'whatsapp_image_url_2',
                'whatsapp_image_url_3',
            ]);
        });
    }
};
