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
        Schema::table('producto_etiquetas', function (Blueprint $table) {
            $table->integer('product_popup_delay_seconds')
                ->default(30)
                ->after('popup_button_text')
                ->comment('Delay en segundos para el popup de este producto especifico.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('producto_etiquetas', function (Blueprint $table) {
            $table->dropColumn('product_popup_delay_seconds');
        });
    }
};