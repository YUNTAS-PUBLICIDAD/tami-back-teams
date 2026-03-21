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
            if (!Schema::hasColumn('producto_etiquetas', 'popup_button_color')) {
                $table->string('popup_button_color')->nullable();
            }
            if (!Schema::hasColumn('producto_etiquetas', 'popup_text_color')) {
                $table->string('popup_text_color')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('producto_etiquetas', function (Blueprint $table) {
            if (Schema::hasColumn('producto_etiquetas', 'popup_button_color')) {
                $table->dropColumn('popup_button_color');
            }
            if (Schema::hasColumn('producto_etiquetas', 'popup_text_color')) {
                $table->dropColumn('popup_text_color');
            }
        });
    }
};
