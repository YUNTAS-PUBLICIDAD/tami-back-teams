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
            $table->integer('whatsapp_time_1_inicio')->change();
            $table->integer('whatsapp_time_2_inicio')->change();
            $table->integer('whatsapp_time_3_inicio')->change();
            $table->integer('whatsapp_time_1_producto')->change();
            $table->integer('whatsapp_time_2_producto')->change();
            $table->integer('whatsapp_time_3_producto')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('home_popup_settings', function (Blueprint $table) {
            $table->unsignedInteger('whatsapp_time_1_inicio')->change();
            $table->unsignedInteger('whatsapp_time_2_inicio')->change();
            $table->unsignedInteger('whatsapp_time_3_inicio')->change();
            $table->unsignedInteger('whatsapp_time_1_producto')->change();
            $table->unsignedInteger('whatsapp_time_2_producto')->change();
            $table->unsignedInteger('whatsapp_time_3_producto')->change();
        });
    }
};
