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
            $table->string('popup_button_color')->nullable();
            $table->string('popup_text_color')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('producto_etiquetas', function (Blueprint $table) {
        $table->dropColumn(['popup_button_color','popup_text_color']);
    });
    }
};
