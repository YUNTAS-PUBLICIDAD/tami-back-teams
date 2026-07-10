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
        Schema::table('blogs_imagenes', function (Blueprint $table) {
            $table->renameColumn('text_alt', 'img_alt');
            $table->string('img_nombre')->nullable()->after('ruta_imagen');
            $table->string('img_tittle')->nullable()->after('img_nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blogs_imagenes', function (Blueprint $table) {
            $table->dropColumn(['img_nombre', 'img_tittle']);
            $table->renameColumn('img_alt', 'text_alt');
        });
    }
};
