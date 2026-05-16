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
    public function up()
    {
        // 1. Limpiar nulos existentes en la tabla home_popup_settings
        foreach (['whatsapp_time_1_producto', 'whatsapp_time_2_producto', 'whatsapp_time_3_producto'] as $column) {
            DB::table('home_popup_settings')->whereNull($column)->update([$column => 0]);
        }
        // 2. Ejecutar el cambio de esquema
        Schema::table('home_popup_settings', function (Blueprint $table) {
            $table->integer('whatsapp_time_1_producto')->default(0)->change();
            $table->integer('whatsapp_time_2_producto')->default(0)->change();
            $table->integer('whatsapp_time_3_producto')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('home_popup_settings', function (Blueprint $table) {
            $table->unsignedInteger('whatsapp_time_1_producto')->nullable()->change();
            $table->unsignedInteger('whatsapp_time_2_producto')->nullable()->change();
            $table->unsignedInteger('whatsapp_time_3_producto')->nullable()->change();
        });
    }
};
