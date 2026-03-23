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
            $table->string('popup_image2_url')->nullable()->after('popup_image_url');
            $table->string('popup_mobile_image_url')->nullable()->after('popup_image2_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('home_popup_settings', function (Blueprint $table) {
            $table->dropColumn(['popup_image2_url', 'popup_mobile_image_url']);
        });
    }
};
