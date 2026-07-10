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
        Schema::table('blogs', function (Blueprint $table) {
            $table->string('miniatura_nombre')->nullable()->after('miniatura');
            $table->string('miniatura_alt')->nullable()->after('miniatura_nombre');
            $table->string('miniatura_tittle')->nullable()->after('miniatura_alt');
            $table->string('hero_image_nombre')->nullable()->after('hero_image');
            $table->string('hero_image_alt')->nullable()->after('hero_image_nombre');
            $table->string('hero_image_tittle')->nullable()->after('hero_image_alt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn([
                'miniatura_nombre',
                'miniatura_alt',
                'miniatura_tittle',
                'hero_image_nombre',
                'hero_image_alt',
                'hero_image_tittle',
            ]);
        });
    }
};
