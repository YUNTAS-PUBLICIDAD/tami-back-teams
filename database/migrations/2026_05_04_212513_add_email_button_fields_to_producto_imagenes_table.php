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
            $table->string('email_btn_text', 100)->nullable()->after('email_mensaje');
            $table->string('email_btn_link', 255)->nullable()->after('email_btn_text');
            $table->string('email_btn_bg_color', 20)->nullable()->after('email_btn_link');
            $table->string('email_btn_text_color', 20)->nullable()->after('email_btn_bg_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('producto_imagenes', function (Blueprint $table) {
            $table->dropColumn(['email_btn_text', 'email_btn_link', 'email_btn_bg_color', 'email_btn_text_color']);
        });
    }
};
