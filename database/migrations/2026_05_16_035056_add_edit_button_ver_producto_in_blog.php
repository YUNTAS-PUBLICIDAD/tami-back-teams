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
        Schema::table('blog_etiquetas', function (Blueprint $table) {

            if (!Schema::hasColumn('blog_etiquetas', 'popup_button_text')) {
                $table->string('popup_button_text', 50)->nullable();
            }

            if (!Schema::hasColumn('blog_etiquetas', 'popup_button_color')) {
                $table->string('popup_button_color', 20)->nullable();
            }

            if (!Schema::hasColumn('blog_etiquetas', 'popup_text_color')) {
                $table->string('popup_text_color', 20)->nullable();
            }

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blog_etiquetas', function (Blueprint $table) {

            if (Schema::hasColumn('blog_etiquetas', 'popup_button_text')) {
                $table->dropColumn('popup_button_text');
            }

            if (Schema::hasColumn('blog_etiquetas', 'popup_button_color')) {
                $table->dropColumn('popup_button_color');
            }

            if (Schema::hasColumn('blog_etiquetas', 'popup_text_color')) {
                $table->dropColumn('popup_text_color');
            }

        });
    }
};